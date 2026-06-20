<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Models\StudentBalance;
use App\Models\TutorProfile;
use App\Services\BookingService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LessonBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function store(Request $request, TutorProfile $tutor, PaymentService $paymentService): RedirectResponse
    {
        abort_unless($tutor->is_verified, 404);

        $validated = $request->validate([
            'slot' => ['nullable', 'date_format:Y-m-d H:i'],
            'slots' => ['nullable', 'array'],
            'slots.*' => ['required', 'date_format:Y-m-d H:i'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+375\d{9}$/'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'package' => ['nullable', 'in:single,pack_4,pack_8'],
            'terms' => ['accepted'],
        ], [
            'phone.regex' => 'Телефон должен быть в формате +375XXXXXXXXX.',
            'terms.accepted' => 'Нужно согласиться с условиями отмены и переноса.',
        ]);

        $notes = $validated['notes'] ?? null;
        $packageCode = $validated['package'] ?? 'single';
        $slotValues = $validated['slots'] ?? array_filter([$validated['slot'] ?? null]);

        try {
            $lesson = $this->bookingService->createPackageBooking(
                tutorProfile: $tutor,
                booker: $request->user(),
                startTimesLocal: $slotValues,
                notes: $notes,
                studentName: $validated['name'],
                studentPhone: $validated['phone'],
                packageCode: $packageCode,
            );
        } catch (SlotUnavailableException $exception) {
            throw ValidationException::withMessages([
                'slot' => $exception->getMessage(),
            ]);
        }

        $walletBalance = StudentBalance::query()->firstWhere('user_id', $request->user()->id);
        $canAutoPayFromWallet = $packageCode === 'single'
            && $walletBalance
            && (float) $walletBalance->available_amount >= (float) $lesson->price;

        if ($canAutoPayFromWallet) {
            try {
                $paymentService->processPayment(
                    lessonId: (int) $lesson->id,
                    userId: (int) $request->user()->id,
                    paymentMethod: 'wallet',
                );

                return redirect()
                    ->route('checkout.success', ['lesson' => $lesson])
                    ->with('checkout_success', 'Урок автоматически оплачен с внутреннего баланса.');
            } catch (ValidationException) {
                // Fall back to classic checkout flow if wallet payment failed due to race/lock.
            }
        }

        return redirect()
            ->route('checkout.show', ['lesson' => $lesson])
            ->with('booking_success', 'Слот зарезервирован на 15 минут. Завершите оплату, чтобы подтвердить урок.');
    }
}
