<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\StudentBalance;
use App\Models\TutorProfile;
use App\Services\PackageService;
use App\Services\Payment\PaymentService;
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

        if ($walletBalance && (float) $walletBalance->available_amount >= (float) $lesson->price) {
            $paymentMethods = ['wallet' => 'Внутренний баланс Edusfera'] + $paymentMethods;
        }

        return view('checkout.show', [
            'lesson' => $lesson,
            'paymentMethods' => $paymentMethods,
            'walletBalance' => (float) ($walletBalance?->available_amount ?? 0),
            'expiresInSeconds' => max((int) now('UTC')->diffInSeconds($lesson->payment_lock_expires_at, false), 0),
        ]);
    }

    public function pay(Request $request, Lesson $lesson, PaymentService $paymentService): RedirectResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        if (! $lesson->hasActivePaymentLock()) {
            $this->expireLessonIfNeeded($lesson);

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

        $packageCode = ($validated['payment_method'] === 'wallet' || $useWalletBalance)
            ? 'single'
            : $validated['package_code'];

        $this->applyPackageSelection($lesson, $packageCode, app(PackageService::class));

        $paymentService->processPayment(
            $lesson->id,
            (int) $request->user()->id,
            $validated['payment_method'],
            (bool) ($validated['remember_card'] ?? false),
            $useWalletBalance,
        );

        return redirect()
            ->route('checkout.success', $lesson)
            ->with('checkout_success', 'Оплата прошла успешно. Урок подтвержден.');
    }

    public function success(Lesson $lesson): View|RedirectResponse
    {
        abort_unless($this->canAccessLesson($lesson), 403);

        $lesson->loadMissing('tutor.tutorProfile', 'student', 'conversation');

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
