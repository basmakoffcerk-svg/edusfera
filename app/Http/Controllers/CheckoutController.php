<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\Transaction;
use App\Models\TutorProfile;
use App\Services\PackageService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Lesson $lesson): View|RedirectResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        $lesson->loadMissing('tutor.tutorProfile', 'student');
        $walletBalance = auth()->user()
            ? StudentBalance::query()->firstWhere('user_id', auth()->id())
            : null;

        if ($lesson->payment_status === Lesson::PAYMENT_PAID) {
            return redirect('/admin/lessons')->with('checkout_success', 'Оплата уже проведена.');
        }

        if (! $lesson->hasActivePaymentLock()) {
            $this->expireLessonIfNeeded($lesson);
            $lesson->refresh();

            // Резерв истёк, но платёж ещё в обработке шлюза (3-D Secure и т.п.) —
            // ведём на страницу подтверждения, а не к повторному выбору слота.
            if ($lesson->status !== Lesson::STATUS_CANCELLED) {
                return redirect()->route('checkout.success', $lesson);
            }

            return redirect()
                ->route('tutors.show', [
                    'tutor' => TutorProfile::query()->where('user_id', $lesson->tutor_id)->firstOrFail(),
                    'date' => $lesson->start_time->setTimezone(config('booking.display_timezone'))->format('Y-m-d'),
                ])
                ->with('booking_error', 'Время резерва истекло. Выберите слот повторно.');
        }

        $paymentMethods = [
            'card' => 'Карта Visa / Mastercard / Белкарт',
            'erip' => 'ЕРИП',
            'apple_pay' => 'Apple Pay',
            'google_pay' => 'Google Pay',
        ];

        return view('checkout.show', [
            'lesson' => $lesson,
            'paymentMethods' => $paymentMethods,
            'walletBalance' => 0.0,
            'expiresInSeconds' => max((int) now('UTC')->diffInSeconds($lesson->payment_lock_expires_at, false), 0),
            'activeGateway' => config('payments.gateway'),
            'webSdkUrl' => config('payments.alfabank.web_sdk_url', 'https://abby.rbsuat.com/payment/modules/multiframe/main.js'),
            'apiContext' => config('payments.alfabank.api_context', '/payment'),
        ]);
    }

    public function initAlfaSdk(Request $request, Lesson $lesson, PaymentService $paymentService): JsonResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        if (! $lesson->hasActivePaymentLock()) {
            $this->expireLessonIfNeeded($lesson);
            $lesson->refresh();

            if ($lesson->status === Lesson::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Резерв времени истек. Выберите слот заново.',
                ], 422);
            }
        }

        $packageCode = (string) $request->input('package_code', 'single');
        if (! in_array($packageCode, ['single', 'pack_4', 'pack_8'], true)) {
            $packageCode = 'single';
        }

        if ($lesson->package_code === null) {
            $this->applyPackageSelection($lesson, $packageCode, app(PackageService::class));
        }

        $useWalletBalance = (bool) $request->input('use_wallet_balance', false);

        $transaction = $paymentService->processPayment(
            $lesson->id,
            (int) $request->user()->id,
            'card',
            (bool) $request->input('remember_card', false),
            $useWalletBalance,
        );

        $gatewayResponse = (array) ($transaction->gateway_response ?? []);
        $mdOrder = $gatewayResponse['mdOrder'] ?? $transaction->gateway_transaction_id;

        return response()->json([
            'success' => true,
            'mdOrder' => $mdOrder,
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'web_sdk_url' => $gatewayResponse['web_sdk_url'] ?? config('payments.alfabank.web_sdk_url', 'https://abby.rbsuat.com/payment/modules/multiframe/main.js'),
            'api_context' => $gatewayResponse['api_context'] ?? config('payments.alfabank.api_context', '/payment'),
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'redirect_url' => $gatewayResponse['redirect_url'] ?? route('checkout.success', $lesson),
        ]);
    }

    public function pay(Request $request, Lesson $lesson, PaymentService $paymentService): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        if ($lesson->payment_status === Lesson::PAYMENT_PAID) {
            return redirect()->route('checkout.success', $lesson);
        }

        if (! $lesson->hasActivePaymentLock()) {
            $this->expireLessonIfNeeded($lesson);
            $lesson->refresh();

            if ($lesson->status !== Lesson::STATUS_CANCELLED) {
                return redirect()->route('checkout.success', $lesson);
            }

            throw ValidationException::withMessages([
                'payment' => 'Резерв времени истек. Выберите слот заново.',
            ]);
        }

        $validated = $request->validate([
            'package_code' => ['required', 'in:single,pack_4,pack_8'],
            'payment_method' => ['required', 'in:wallet,card,erip,apple_pay,google_pay'],
            'use_wallet_balance' => ['nullable', 'boolean'],
            'remember_card' => ['nullable', 'boolean'],
        ]);

        $useWalletBalance = (bool) ($validated['use_wallet_balance'] ?? false);

        $packageCode = $validated['package_code'];

        // Пакет фиксируется при бронировании. Прежний код слепо перезаписывал
        // package_total по пользовательскому package_code: забронировав pack_4
        // и оплатив как single, ученик получал 4 подтверждённых урока по цене
        // одного. Менять пакет на этапе оплаты нельзя.
        if ($lesson->package_code !== null && $lesson->package_code !== $packageCode) {
            throw ValidationException::withMessages([
                'package_code' => 'Пакет оплаты зафиксирован при бронировании и не может быть изменён.',
            ]);
        }

        if ($lesson->package_code === null) {
            $this->applyPackageSelection($lesson, $packageCode, app(PackageService::class));
        }

        $transaction = $paymentService->processPayment(
            $lesson->id,
            (int) $request->user()->id,
            $validated['payment_method'],
            (bool) ($validated['remember_card'] ?? false),
            $useWalletBalance,
        );

        if (
            in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AUTHORIZED], true)
            && $transaction->gateway_transaction_id !== null
        ) {
            $isTest = (bool) config('payments.alfabank.test_mode', true) || app()->environment('local', 'testing');
            if ($isTest || $paymentService->verifyPayment((string) $transaction->gateway_transaction_id)) {
                $paymentService->capturePendingPayment($transaction);
                $lesson->refresh();

                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'status' => 'success',
                        'redirect_url' => route('checkout.success', $lesson),
                    ]);
                }

                return redirect()
                    ->route('checkout.success', $lesson)
                    ->with('checkout_success', 'Оплата прошла успешно. Урок подтвержден.');
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'mdOrder' => $transaction->gateway_response['mdOrder'] ?? null,
                'web_sdk_url' => $transaction->gateway_response['web_sdk_url'] ?? config('payments.alfabank.web_sdk_url'),
                'api_context' => $transaction->gateway_response['api_context'] ?? config('payments.alfabank.api_context'),
                'redirect_url' => $transaction->gateway_response['redirect_url'] ?? route('checkout.success', $lesson),
            ]);
        }

        if (
            in_array($transaction->status, [Transaction::STATUS_PENDING, Transaction::STATUS_AUTHORIZED], true)
            && ! empty($transaction->gateway_response['redirect_url'])
        ) {
            return redirect()->away($transaction->gateway_response['redirect_url']);
        }

        return redirect()
            ->route('checkout.success', $lesson)
            ->with('checkout_success', 'Оплата прошла успешно. Урок подтвержден.');
    }

    public function success(Lesson $lesson, PaymentService $paymentService): View|RedirectResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        $lesson->loadMissing('tutor.tutorProfile', 'student', 'conversation');

        if ($lesson->payment_status !== Lesson::PAYMENT_PAID) {
            $pendingTransaction = Transaction::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [
                    Transaction::STATUS_PENDING,
                    Transaction::STATUS_AUTHORIZED,
                ])
                ->first();

            if ($pendingTransaction && $pendingTransaction->gateway_transaction_id) {
                $isTest = (bool) config('payments.alfabank.test_mode', true) || app()->environment('local', 'testing');
                if ($isTest || $paymentService->verifyPayment($pendingTransaction->gateway_transaction_id)) {
                    $paymentService->capturePendingPayment($pendingTransaction);
                    $lesson->refresh();
                }
            }
        }

        if ($lesson->payment_status !== Lesson::PAYMENT_PAID) {
            return redirect()->route('checkout.show', $lesson);
        }

        return view('checkout.success', [
            'lesson' => $lesson,
            'googleCalendarUrl' => $this->googleCalendarUrl($lesson),
            'calendarDownloadUrl' => route('checkout.calendar', $lesson),
            'chatUrl' => '/admin/messages?conversation='.optional($lesson->conversation)->id,
        ]);
    }

    public function calendar(Lesson $lesson): Response
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        $lesson->loadMissing('tutor.tutorProfile');

        $subject = $lesson->tutor?->tutorProfile?->subjects[0] ?? 'Урок';
        $summary = "Edusfera: {$subject} с {$lesson->tutor?->name}";
        $description = 'Занятие забронировано и оплачено через Edusfera.';
        $location = $lesson->meeting_link ?: 'Edusfera';

        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Edusfera//Lesson Booking//RU',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:lesson-'.$lesson->id.'@edusfera.by',
            'DTSTAMP:'.now('UTC')->format('Ymd\THis\Z'),
            'DTSTART:'.$lesson->start_time->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$lesson->end_time->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escapeIcs($summary),
            'DESCRIPTION:'.$this->escapeIcs($description),
            'LOCATION:'.$this->escapeIcs($location),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="edusfera-lesson-'.$lesson->id.'.ics"',
        ]);
    }

    private function canAccessLesson(Lesson $lesson): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return in_array($user->id, array_filter([$lesson->student_id, $lesson->parent_id]), true);
    }

    private function expireLessonIfNeeded(Lesson $lesson): void
    {
        if (
            $lesson->payment_status === Lesson::PAYMENT_UNPAID
            && $lesson->payment_lock_expires_at !== null
            && $lesson->payment_lock_expires_at->isPast()
            && $lesson->status === Lesson::STATUS_PENDING
        ) {
            // Не отменяем урок, пока жива авторизация/платёж в шлюзе: студент
            // мог уйти на 3-D Secure, задержаться дольше 15 минут и вернуться.
            // Отмена здесь освобождала слот, второй ученик брал и оплачивал его,
            // а поздний webhook «completed» по первой транзакции воскрешал
            // отменённый урок → два оплаченных урока на одном слоте.
            $hasOpenGatewayPayment = Transaction::query()
                ->where('lesson_id', $lesson->id)
                ->whereIn('status', [
                    Transaction::STATUS_AUTHORIZED,
                    Transaction::STATUS_PENDING,
                ])
                ->exists();

            if ($hasOpenGatewayPayment) {
                return;
            }

            $lesson->update([
                'status' => Lesson::STATUS_CANCELLED,
            ]);

            Lesson::query()
                ->where('package_parent_lesson_id', $lesson->id)
                ->update([
                    'status' => Lesson::STATUS_CANCELLED,
                ]);
        }
    }

    private function applyPackageSelection(Lesson $lesson, string $packageCode, PackageService $packageService): void
    {
        $packageService->applyToLesson($lesson, $packageCode);
    }

    private function googleCalendarUrl(Lesson $lesson): string
    {
        $subject = $lesson->tutor?->tutorProfile?->subjects[0] ?? 'Урок';
        $text = "Edusfera: {$subject} с {$lesson->tutor?->name}";
        $dates = $lesson->start_time->utc()->format('Ymd\THis\Z').'/'.$lesson->end_time->utc()->format('Ymd\THis\Z');

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text='
            .urlencode($text)
            .'&dates='.urlencode($dates)
            .'&details='.urlencode('Занятие забронировано и оплачено через Edusfera.')
            .'&location='.urlencode($lesson->meeting_link ?: 'Edusfera');
    }

    private function escapeIcs(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', ''],
            $value,
        );
    }
}
