<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Services\BookingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogController extends Controller
{
    public function __construct(private readonly BookingService $bookingService) {}

    public function index(Request $request)
    {
        $baseQuery = TutorProfile::query()->where('is_verified', true);
        $query = TutorProfile::with('user')->where('is_verified', true);

        $query->orderByRaw(
            'CASE WHEN search_penalized_until IS NOT NULL AND search_penalized_until > ? THEN 1 ELSE 0 END ASC',
            [now('UTC')]
        );

        if ($request->filled('q')) {
            $search = str_replace(['%', '_'], ['\%', '\_'], trim((string) $request->q));

            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('subject')) {
            $query->whereJsonContains('subjects', $request->subject);
        }

        if ($request->filled('price_max')) {
            $query->where('price_per_hour', '<=', $request->price_max);
        }

        if ($request->boolean('exam_track')) {
            $query->where(function ($builder): void {
                $builder
                    ->whereJsonContains('audiences', 'Подготовка к ЦЭ')
                    ->orWhereJsonContains('audiences', 'Подготовка к ЦТ')
                    ->orWhereJsonContains('exam_specializations', 'ЦЭ')
                    ->orWhereJsonContains('exam_specializations', 'ЦТ');
            });
        }

        if ($request->boolean('diagnostic_supported')) {
            $query->where('diagnostic_supported', true);
        }

        if ($request->boolean('official')) {
            $query->where('legal_status', '!=', 'none');
        }

        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price_per_hour'),
            'price_desc' => $query->orderByDesc('price_per_hour'),
            'experience' => $query->orderByDesc('experience_years')->orderByDesc('rating_avg'),
            'outcomes' => $query
                ->orderByDesc('students_prepared_count')
                ->orderByDesc('average_score_growth')
                ->orderByDesc('max_recent_score'),
            default => $query->orderByDesc('rating_avg')->orderByDesc('created_at'),
        };

        $tutors = $query
            ->paginate(12)
            ->withQueryString();

        $allSubjects = [
            'Математика', 'Физика', 'Химия', 'Биология',
            'Английский язык', 'Русский язык', 'Белорусский язык',
            'История', 'Информатика',
        ];

        $ratedBaseQuery = (clone $baseQuery)->where('rating_avg', '>', 0);
        $hasRealRating = $ratedBaseQuery->exists();
        $availabilityHints = $this->buildAvailabilityHints($tutors->getCollection()->pluck('user_id')->all());

        $stats = [
            'tutors_count' => (clone $baseQuery)->count(),
            'rating_avg' => $hasRealRating ? number_format((float) $ratedBaseQuery->avg('rating_avg'), 1) : '4.9',
            'has_real_rating' => $hasRealRating,
            'official_share' => (int) round(
                ((clone $baseQuery)->where('legal_status', '!=', 'none')->count()
                    / max((clone $baseQuery)->count(), 1)) * 100
            ),
            'subjects_count' => count($allSubjects),
        ];

        return view('catalog.index', [
            'tutors' => $tutors,
            'allSubjects' => $allSubjects,
            'stats' => $stats,
            'availabilityHints' => $availabilityHints,
        ]);
    }

    public function show(TutorProfile $tutor)
    {
        abort_unless($tutor->is_verified, 404);

        $tutor->load('user');
        $selectedDate = request('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', (string) request('date'), $this->bookingService->displayTimezone())
            : $this->bookingService->minBookableDate();

        if ($selectedDate === false) {
            $selectedDate = $this->bookingService->minBookableDate();
        }

        $selectedDate = $selectedDate->startOfDay();
        $slots = $this->bookingService->getAvailableSlots($tutor, $selectedDate);

        $canStartConversation = auth()->check() && in_array(auth()->user()->role, ['student', 'parent'], true);

        return view('catalog.show', compact('tutor', 'selectedDate', 'slots', 'canStartConversation'));
    }

    /**
     * @param  array<int, int>  $tutorUserIds
     * @return array<int, string>
     */
    private function buildAvailabilityHints(array $tutorUserIds): array
    {
        if ($tutorUserIds === []) {
            return [];
        }

        $timezone = $this->bookingService->displayTimezone();
        $from = CarbonImmutable::now($timezone)->startOfDay();
        $windowsByTutor = TutorAvailability::query()
            ->whereIn('user_id', $tutorUserIds)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('user_id');

        $hints = [];

        foreach ($tutorUserIds as $userId) {
            /** @var Collection<int, TutorAvailability> $windows */
            $windows = $windowsByTutor->get($userId, collect());

            if ($windows->isEmpty()) {
                $hints[$userId] = 'Ближайшее окно уточняется';

                continue;
            }

            $nextSlotLabel = null;

            for ($dayOffset = 1; $dayOffset <= 7; $dayOffset++) {
                $date = $from->addDays($dayOffset);
                $window = $windows->firstWhere('day_of_week', $date->dayOfWeek);

                if (! $window instanceof TutorAvailability) {
                    continue;
                }

                $time = substr((string) $window->start_time, 0, 5);
                $nextSlotLabel = $dayOffset === 1
                    ? "Ближайшее окно: завтра в {$time}"
                    : "Ближайшее окно: {$date->translatedFormat('D')} в {$time}";

                break;
            }

            $hints[$userId] = $nextSlotLabel ?? 'Ближайшее окно уточняется';
        }

        return $hints;
    }
}
