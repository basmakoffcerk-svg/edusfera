<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TutorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_can_filter_exam_track_and_diagnostic_support(): void
    {
        $examTutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Экзамен Репетитор',
            'phone' => '+375298400001',
        ]);

        TutorProfile::query()->create([
            'user_id' => $examTutor->id,
            'subjects' => ['Белорусский язык'],
            'audiences' => ['Подготовка к ЦЭ'],
            'price_per_hour' => 45,
            'experience_years' => 6,
            'legal_status' => 'self_employed',
            'bio' => 'Экзаменационный трек и диагностика.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'exam_specializations' => ['ЦЭ', 'score_growth'],
            'average_score_growth' => 18,
            'students_prepared_count' => 27,
            'max_recent_score' => 91,
            'diagnostic_supported' => true,
        ]);

        $genericTutor = User::factory()->create([
            'role' => 'tutor',
            'name' => 'Обычный Репетитор',
            'phone' => '+375298400002',
        ]);

        TutorProfile::query()->create([
            'user_id' => $genericTutor->id,
            'subjects' => ['История'],
            'audiences' => ['9-11 классы'],
            'price_per_hour' => 30,
            'experience_years' => 4,
            'legal_status' => 'none',
            'bio' => 'Обычная школьная подготовка.',
            'is_verified' => true,
            'verification_status' => 'approved',
            'diagnostic_supported' => false,
        ]);

        $this->get('/tutors?exam_track=1&diagnostic_supported=1')
            ->assertOk()
            ->assertSee('Экзамен')
            ->assertDontSee('Обычная школьная подготовка.');
    }
}
