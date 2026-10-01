<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Subscription\Models\Subscription;
use App\Enums\UserRole;
use App\Models\TutorAvailability;
use App\Models\TutorProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogEdgeCasesComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    private function createTutorUser(string $name, string $phone = '+375291112233'): User
    {
        return User::factory()->create([
            'role' => UserRole::Tutor,
            'name' => $name,
            'phone' => $phone,
        ]);
    }

    private function createTutorProfile(User $user, array $attributes = []): TutorProfile
    {
        return TutorProfile::query()->create(array_merge([
            'user_id' => $user->id,
            'subjects' => ['Математика'],
            'audiences' => ['5-9 классы'],
            'price_per_hour' => 40,
            'experience_years' => 5,
            'legal_status' => 'self_employed',
            'bio' => 'Опытный преподаватель точных наук.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'diagnostic_supported' => false,
            'rating_avg' => 4.8,
            'students_prepared_count' => 10,
            'average_score_growth' => 15,
            'max_recent_score' => 88,
        ], $attributes));
    }

    #[DataProvider('searchSanitizationProvider')]
    public function test_query_sanitization_and_special_characters(string $queryInput, string $targetName, bool $shouldFind): void
    {
        $targetUser = $this->createTutorUser($targetName, '+375291110001');
        $this->createTutorProfile($targetUser);

        $otherUser = $this->createTutorUser('Другой Тьютор', '+375291110002');
        $this->createTutorProfile($otherUser);

        $response = $this->get('/tutors?q='.urlencode($queryInput));
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $targetUser->id);

        $this->assertSame($shouldFind, $found);
    }

    public static function searchSanitizationProvider(): array
    {
        return [
            'exact substring' => ['Алексей', 'Алексей Иванов', true],
            'ascii lowercase match' => ['alex', 'Alex Smith', true],
            'ascii uppercase match' => ['ALEX', 'Alex Smith', true],
            'wildcard percent escaped' => ['%', 'Алексей Иванов', false],
            'wildcard underscore escaped' => ['_', 'Алексей Иванов', false],
            'literal percent in name' => ['100%', 'Репетитор 100% Результат', true],
            'apostrophe in query' => ["д'Артаньян", "Жан д'Артаньян", true],
            'double quote in query' => ['"Профи"', 'Алексей "Профи" Смирнов', true],
            'backslash in query' => ['\\', 'Алексей Иванов', false],
            'sql injection attempt union' => ["' UNION SELECT * FROM users--", 'Алексей Иванов', false],
            'sql injection attempt or true' => ["' OR '1'='1", 'Алексей Иванов', false],
            'cyrillic with yo letter' => ['Фёдор', 'Фёдор Михайлович', true],
            'whitespace trimmed' => ['   Алексей   ', 'Алексей Иванов', true],
            'unicode emoji query' => ['🎓', 'Алексей Иванов', false],
            'hyphenated compound name' => ['Мария-Анна', 'Мария-Анна Белова', true],
            'short two letter token' => ['Ив', 'Алексей Иванов', true],
            'single character query' => ['А', 'Алексей Иванов', true],
            'query not matching' => ['Владислав', 'Алексей Иванов', false],
            'special chars bracket' => ['[VIP]', 'Алексей [VIP] Репетитор', true],
            'long query string 100 chars' => [str_repeat('тест', 25), 'Алексей Иванов', false],
        ];
    }

    #[DataProvider('subjectFilterProvider')]
    public function test_subject_filter_permutations(string $filterSubject, array $tutorSubjects, bool $expectedMatch): void
    {
        $user = $this->createTutorUser('Тьютор Предметный', '+375292220001');
        $this->createTutorProfile($user, ['subjects' => $tutorSubjects]);

        $response = $this->get('/tutors?subject='.urlencode($filterSubject));
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($expectedMatch, $found);
    }

    public static function subjectFilterProvider(): array
    {
        return [
            'canonical math' => ['Математика', ['Математика'], true],
            'canonical physics' => ['Физика', ['Физика', 'Математика'], true],
            'canonical chemistry' => ['Химия', ['Химия'], true],
            'canonical biology' => ['Биология', ['Биология'], true],
            'canonical english' => ['Английский язык', ['Английский язык'], true],
            'canonical russian' => ['Русский язык', ['Русский язык'], true],
            'canonical belarusian' => ['Белорусский язык', ['Белорусский язык'], true],
            'canonical history' => ['История', ['История'], true],
            'canonical computer science' => ['Информатика', ['Информатика'], true],
            'subject not taught by tutor' => ['Химия', ['Математика', 'Физика'], false],
            'multiple subjects contains target' => ['Математика', ['Английский язык', 'Математика', 'Информатика'], true],
            'empty subject string' => ['', ['Математика'], true],
            'non-existent discipline' => ['Астрономия Древнего Египта', ['Математика'], false],
            'partial subject string does not match full json string' => ['Матем', ['Математика'], false],
            'case sensitive json exact match' => ['математика', ['Математика'], false],
        ];
    }

    #[DataProvider('priceBoundaryProvider')]
    public function test_price_boundary_conditions(string|int|float $maxPriceFilter, int|float $tutorPrice, bool $shouldAppear): void
    {
        $user = $this->createTutorUser('Тьютор Прайс', '+375293330001');
        $this->createTutorProfile($user, ['price_per_hour' => $tutorPrice]);

        $response = $this->get('/tutors?price_max='.$maxPriceFilter);
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($shouldAppear, $found);
    }

    public static function priceBoundaryProvider(): array
    {
        return [
            'exact equality price 30' => [30, 30, true],
            'price below max' => [50, 30, true],
            'price above max by 1' => [29, 30, false],
            'high boundary 1000' => [1000, 150, true],
            'minimal integer price 1' => [1, 1, true],
            'boundary 0 max' => [0, 25, false],
            'zero price tutor matches zero max' => [0, 0, true],
            'string price 45' => ['45', 45, true],
            'string price 40 with tutor 45' => ['40', 45, false],
            'float price 25.50' => [25.50, 25, true],
            'float price 25.50 tutor 26' => [25.50, 26, false],
            'fractional boundary 35.99' => [35.99, 35, true],
            'negative price filter' => [-10, 30, false],
            'very large max price 99999' => [99999, 500, true],
            'boundary 10' => [10, 10, true],
            'boundary 10 tutor 11' => [10, 11, false],
            'boundary 75 tutor 75' => [75, 75, true],
            'boundary 75 tutor 76' => [75, 76, false],
        ];
    }

    #[DataProvider('examTrackProvider')]
    public function test_exam_track_audiences_and_specializations(array $audiences, array $specs, bool $matchesExamTrack): void
    {
        $user = $this->createTutorUser('Экзаменатор', '+375294440001');
        $this->createTutorProfile($user, [
            'audiences' => $audiences,
            'exam_specializations' => $specs,
        ]);

        $response = $this->get('/tutors?exam_track=1');
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($matchesExamTrack, $found);
    }

    public static function examTrackProvider(): array
    {
        return [
            'audience CE' => [['Подготовка к ЦЭ'], [], true],
            'audience CT' => [['Подготовка к ЦТ'], [], true],
            'spec CE' => [['Школьники'], ['ЦЭ'], true],
            'spec CT' => [['Школьники'], ['ЦТ'], true],
            'both audience and spec' => [['Подготовка к ЦЭ'], ['ЦТ'], true],
            'school general only' => [['5-9 классы', '10-11 классы'], [], false],
            'olympiad only' => [['Олимпиады'], ['olympiad'], false],
            'ege russian spec without CE CT' => [['ЕГЭ'], ['ЕГЭ'], false],
            'empty audiences and specs' => [[], [], false],
            'mixed audience with CT' => [['1-4 классы', 'Подготовка к ЦТ'], [], true],
            'mixed spec with CE' => [[], ['score_growth', 'ЦЭ'], true],
            'regular homework support' => [['Домашние задания'], ['homework'], false],
        ];
    }

    #[DataProvider('officialStatusProvider')]
    public function test_official_status_permutations(string $status, bool $expectedWhenOfficialFiltered): void
    {
        $user = $this->createTutorUser('Официальный Тьютор', '+375295550001');
        $this->createTutorProfile($user, ['legal_status' => $status]);

        $response = $this->get('/tutors?official=1');
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($expectedWhenOfficialFiltered, $found);
    }

    public static function officialStatusProvider(): array
    {
        return [
            'self employed' => ['self_employed', true],
            'ip status' => ['ip', true],
            'npd status' => ['npd', true],
            'none status' => ['none', false],
        ];
    }

    #[DataProvider('diagnosticFilterProvider')]
    public function test_diagnostic_supported_filter(bool $profileSupported, string $filterValue, bool $shouldSee): void
    {
        $user = $this->createTutorUser('Диагност', '+375296660001');
        $this->createTutorProfile($user, ['diagnostic_supported' => $profileSupported]);

        $response = $this->get('/tutors?diagnostic_supported='.$filterValue);
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($shouldSee, $found);
    }

    public static function diagnosticFilterProvider(): array
    {
        return [
            'supported with filter 1' => [true, '1', true],
            'supported with filter true' => [true, 'true', true],
            'not supported with filter 1' => [false, '1', false],
            'not supported with filter 0' => [false, '0', true],
        ];
    }

    #[DataProvider('sortingProvider')]
    public function test_sorting_options_permutations(string $sortKey, int $priceA, int $priceB, string $expectedFirst): void
    {
        $userA = $this->createTutorUser('Тьютор Альфа', '+375297770001');
        $this->createTutorProfile($userA, [
            'price_per_hour' => $priceA,
            'rating_avg' => 4.5,
            'experience_years' => 3,
            'students_prepared_count' => 5,
        ]);

        $userB = $this->createTutorUser('Тьютор Бета', '+375297770002');
        $this->createTutorProfile($userB, [
            'price_per_hour' => $priceB,
            'rating_avg' => 4.9,
            'experience_years' => 10,
            'students_prepared_count' => 20,
        ]);

        $response = $this->get('/tutors?sort='.$sortKey);
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $this->assertCount(2, $tutors);

        $firstItemUserId = $tutors->first()->user_id;

        if ($expectedFirst === 'Альфа') {
            $this->assertSame($userA->id, $firstItemUserId);
        } else {
            $this->assertSame($userB->id, $firstItemUserId);
        }
    }

    public static function sortingProvider(): array
    {
        return [
            'price asc when A cheaper' => ['price_asc', 20, 50, 'Альфа'],
            'price asc when B cheaper' => ['price_asc', 60, 30, 'Бета'],
            'price desc when A more expensive' => ['price_desc', 80, 40, 'Альфа'],
            'price desc when B more expensive' => ['price_desc', 25, 75, 'Бета'],
            'experience sort prefers higher experience' => ['experience', 40, 40, 'Бета'],
            'outcomes sort prefers higher student count' => ['outcomes', 40, 40, 'Бета'],
            'default sort prefers higher rating' => ['default', 40, 40, 'Бета'],
            'empty sort falls back to default rating' => ['', 40, 40, 'Бета'],
            'unknown sort key falls back to default rating' => ['random_unknown_sort', 40, 40, 'Бета'],
            'match sort with no context falls back gracefully' => ['match', 40, 40, 'Бета'],
        ];
    }

    #[DataProvider('subscriptionRankingProvider')]
    public function test_subscription_priority_ranking(string $planA, string $planB, string $expectedFirst): void
    {
        $userA = $this->createTutorUser('Подписчик А', '+375298880001');
        $this->createTutorProfile($userA);
        if ($planA !== 'none') {
            Subscription::query()->create([
                'tutor_id' => $userA->id,
                'plan' => $planA,
                'status' => 'active',
                'current_period_start' => now()->subDay(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        $userB = $this->createTutorUser('Подписчик Б', '+375298880002');
        $this->createTutorProfile($userB);
        if ($planB !== 'none') {
            Subscription::query()->create([
                'tutor_id' => $userB->id,
                'plan' => $planB,
                'status' => 'active',
                'current_period_start' => now()->subDay(),
                'current_period_end' => now()->addMonth(),
            ]);
        }

        $response = $this->get('/tutors');
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $firstItemUserId = $tutors->first()->user_id;

        if ($expectedFirst === 'А') {
            $this->assertSame($userA->id, $firstItemUserId);
        } else {
            $this->assertSame($userB->id, $firstItemUserId);
        }
    }

    public static function subscriptionRankingProvider(): array
    {
        return [
            'premium vs pro' => ['premium', 'pro', 'А'],
            'pro vs premium' => ['pro', 'premium', 'Б'],
            'premium vs basic' => ['premium', 'basic', 'А'],
            'pro vs basic' => ['pro', 'basic', 'А'],
            'basic vs none' => ['basic', 'none', 'А'],
            'none vs pro' => ['none', 'pro', 'Б'],
            'premium vs none' => ['premium', 'none', 'А'],
            'none vs basic' => ['none', 'basic', 'Б'],
        ];
    }

    #[DataProvider('searchPenalizedProvider')]
    public function test_penalized_tutors_placement(?string $penaltyOffset, bool $expectPenalizedLast): void
    {
        $userGood = $this->createTutorUser('Тьютор Хороший', '+375299990001');
        $this->createTutorProfile($userGood, ['rating_avg' => 4.0]);

        $userPenalized = $this->createTutorUser('Тьютор Наказанный', '+375299990002');
        $penalizedUntil = $penaltyOffset ? now()->modify($penaltyOffset) : null;
        $this->createTutorProfile($userPenalized, [
            'rating_avg' => 5.0,
            'search_penalized_until' => $penalizedUntil,
        ]);

        $response = $this->get('/tutors');
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $firstItemUserId = $tutors->first()->user_id;

        if ($expectPenalizedLast) {
            $this->assertSame($userGood->id, $firstItemUserId);
        } else {
            $this->assertSame($userPenalized->id, $firstItemUserId);
        }
    }

    public static function searchPenalizedProvider(): array
    {
        return [
            'penalty active 1 hour ahead' => ['+1 hour', true],
            'penalty active 2 days ahead' => ['+2 days', true],
            'penalty active 30 minutes ahead' => ['+30 minutes', true],
            'penalty expired 1 hour ago' => ['-1 hour', false],
            'no penalty null' => [null, false],
        ];
    }

    #[DataProvider('verifiedStatusProvider')]
    public function test_verified_status_isolation(bool $isVerified, string $verifStatus, bool $visible): void
    {
        $user = $this->createTutorUser('Тьютор Верификация', '+375291230001');
        $this->createTutorProfile($user, [
            'is_verified' => $isVerified,
            'verification_status' => $verifStatus,
        ]);

        $response = $this->get('/tutors');
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $found = $tutors->contains(fn ($p) => $p->user_id === $user->id);

        $this->assertSame($visible, $found);
    }

    public static function verifiedStatusProvider(): array
    {
        return [
            'verified approved' => [true, 'approved', true],
            'not verified pending' => [false, 'pending', false],
            'not verified rejected' => [false, 'rejected', false],
            'not verified draft' => [false, 'draft', false],
            'not verified approved flag inconsistency' => [false, 'approved', false],
        ];
    }

    #[DataProvider('availabilityHintsProvider')]
    public function test_availability_hints_edge_cases(int $dayOfWeek, string $startTime, string $endTime, bool $isActive): void
    {
        $user = $this->createTutorUser('Тьютор Расписание', '+375293210001');
        $profile = $this->createTutorProfile($user);

        TutorAvailability::query()->create([
            'user_id' => $user->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => $isActive,
        ]);

        $response = $this->get('/tutors');
        $response->assertStatus(200);

        $hints = $response->viewData('availabilityHints');
        $this->assertArrayHasKey($user->id, $hints);
        $this->assertNotEmpty($hints[$user->id]);
    }

    public static function availabilityHintsProvider(): array
    {
        return [
            'monday active slot' => [1, '10:00', '12:00', true],
            'tuesday active slot' => [2, '14:00', '16:00', true],
            'wednesday active slot' => [3, '11:00', '13:00', true],
            'thursday active slot' => [4, '15:00', '17:00', true],
            'friday active slot' => [5, '09:00', '11:00', true],
            'saturday active slot' => [6, '12:00', '14:00', true],
            'sunday active slot' => [0, '13:00', '15:00', true],
            'inactive slot ignored' => [1, '10:00', '12:00', false],
        ];
    }

    #[DataProvider('diagnosticMatchProvider')]
    public function test_diagnostic_match_score_calculation(array $diagContext, array $profileData, bool $isTopMatch): void
    {
        $userTarget = $this->createTutorUser('Идеальный Тьютор', '+375295551111');
        $this->createTutorProfile($userTarget, array_merge([
            'subjects' => [$diagContext['subject'] ?? 'Математика'],
            'audiences' => ['Подготовка к ЦТ'],
            'diagnostic_supported' => true,
            'average_score_growth' => 20,
        ], $profileData));

        $userOther = $this->createTutorUser('Другой Профиль', '+375295552222');
        $this->createTutorProfile($userOther, [
            'subjects' => ['Биология'],
            'audiences' => ['1-4 классы'],
            'diagnostic_supported' => false,
            'average_score_growth' => 2,
        ]);

        $url = '/tutors?sort=match&'.http_build_query($diagContext);
        $response = $this->get($url);
        $response->assertStatus(200);

        $tutors = $response->viewData('tutors');
        $firstItemUserId = $tutors->first()->user_id;

        if ($isTopMatch) {
            $this->assertSame($userTarget->id, $firstItemUserId);
        }
    }

    public static function diagnosticMatchProvider(): array
    {
        return [
            'subject match math' => [['subject' => 'Математика'], [], true],
            'subject match physics' => [['subject' => 'Физика'], ['subjects' => ['Физика']], true],
            'subject match chemistry' => [['subject' => 'Химия'], ['subjects' => ['Химия']], true],
            'subject match english' => [['subject' => 'Английский язык'], ['subjects' => ['Английский язык']], true],
            'subject match biology' => [['subject' => 'Биология'], ['subjects' => ['Биология']], true],
            'subject match russian' => [['subject' => 'Русский язык'], ['subjects' => ['Русский язык']], true],
            'subject match belarusian' => [['subject' => 'Белорусский язык'], ['subjects' => ['Белорусский язык']], true],
            'subject match history' => [['subject' => 'История'], ['subjects' => ['История']], true],
            'subject match informatics' => [['subject' => 'Информатика'], ['subjects' => ['Информатика']], true],
            'with goal score gap' => [['subject' => 'Математика', 'score_gap' => 25], ['average_score_growth' => 25], true],
        ];
    }

    #[DataProvider('catalogShowDateProvider')]
    public function test_catalog_show_date_parameter_edge_cases(string $dateParam): void
    {
        $user = $this->createTutorUser('Тьютор Детали', '+375296667788');
        $profile = $this->createTutorProfile($user);

        $response = $this->get('/tutors/'.$profile->id.'?date='.urlencode($dateParam));
        $response->assertStatus(200);
        $this->assertSame($profile->id, $response->viewData('tutor')->id);
    }

    public static function catalogShowDateProvider(): array
    {
        return [
            'valid today' => [CarbonImmutable::now()->format('Y-m-d')],
            'valid tomorrow' => [CarbonImmutable::now()->addDay()->format('Y-m-d')],
            'valid week later' => [CarbonImmutable::now()->addDays(7)->format('Y-m-d')],
            'invalid date text' => ['invalid-date-string'],
            'out of bound month' => ['2026-13-45'],
            'negative date' => ['-2026-01-01'],
            'sql injection date' => ["' OR '1'='1"],
            'empty date string' => [''],
        ];
    }

    #[DataProvider('canStartConversationRoleProvider')]
    public function test_can_start_conversation_role_matrix(?UserRole $role, bool $shouldCanBook): void
    {
        $tutorUser = $this->createTutorUser('Тьютор Чат', '+375299998877');
        $profile = $this->createTutorProfile($tutorUser);

        if ($role !== null) {
            $visitor = User::factory()->create(['role' => $role]);
            $this->actingAs($visitor);
        }

        $response = $this->get('/tutors/'.$profile->id);
        $response->assertStatus(200);
        $this->assertEquals($shouldCanBook, $response->viewData('canStartConversation'));
    }

    public static function canStartConversationRoleProvider(): array
    {
        return [
            'student role can book' => [UserRole::Student, true],
            'parent role can book' => [UserRole::Parent, true],
            'tutor role cannot book' => [UserRole::Tutor, false],
            'admin role cannot book' => [UserRole::Admin, false],
            'guest unauthenticated cannot book' => [null, false],
        ];
    }
}
