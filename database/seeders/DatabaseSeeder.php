<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentBalance;
use App\Models\TutorBalance;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Создаем репетитора (Tutor)
        $tutor = User::query()->updateOrCreate(
            ['email' => 'tutor@edusfera.by'],
            [
                'name' => 'Иван Репетитор',
                'password' => Hash::make('password'),
                'role' => 'tutor',
                'phone' => '+375291111111',
            ]
        );

        $tutor->tutorProfile()->updateOrCreate(
            ['user_id' => $tutor->id],
            [
                'subjects' => ['Математика', 'Физика'],
                'audiences' => ['Подготовка к ЦЭ', '9 класс', '10 класс'],
                'price_per_hour' => '40.00',
                'experience_years' => 5,
                'legal_status' => 'self_employed',
                'bio' => 'Опытный преподаватель математики и физики. Подготовка к выпускным и вступительным экзаменам.',
                'is_verified' => true,
                'verification_status' => 'approved',
                'lesson_formats' => ['individual_online'],
            ]
        );

        TutorBalance::query()->updateOrCreate(
            ['user_id' => $tutor->id],
            [
                'available_amount' => '0.00',
                'pending_amount' => '0.00',
                'total_earned' => '0.00',
                'total_withdrawn' => '0.00',
            ]
        );

        // 2. Создаем ученика (Student)
        $student = User::query()->updateOrCreate(
            ['email' => 'student@edusfera.by'],
            [
                'name' => 'Петр Ученик',
                'password' => Hash::make('password'),
                'role' => 'student',
                'phone' => '+375292222222',
            ]
        );

        StudentBalance::query()->updateOrCreate(
            ['user_id' => $student->id],
            [
                'available_amount' => '1000.00', // Предварительно пополнен для тестов оплат
                'locked_amount' => '0.00',
                'total_topped_up' => '1000.00',
                'total_spent' => '0.00',
                'total_refunded' => '0.00',
            ]
        );

        // 3. Создаем администратора (Admin) для доступа в Filament панель
        User::query()->updateOrCreate(
            ['email' => 'admin@edusfera.by'],
            [
                'name' => 'Администратор Эдусфера',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+375293333333',
            ]
        );
    }
}
