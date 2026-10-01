<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\SlotUnavailableException;
use App\Models\TutorProfile;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LessonBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function store(Request $request, TutorProfile $tutor): RedirectResponse
    {
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

        $formattedDate = $lesson->start_time
            ?->clone()
            ->setTimezone($this->bookingService->displayTimezone())
            ->format('Y-m-d');

        return redirect()
            ->route('tutors.show', array_filter(['tutor' => $tutor, 'date' => $formattedDate]))
            ->with('booking_success', 'Занятие успешно забронировано! Репетитор получил уведомление и свяжется с вами для подтверждения.');
    }
}
