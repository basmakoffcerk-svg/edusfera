<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Subscription\Models\Subscription;
use App\Models\ClassroomSession;
use App\Models\Lesson;
use App\Models\TutorProfile;
use App\Models\User;
use App\Services\Classroom\LiveKitService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PrepareClassroomTestCommand extends Command
{
    protected $signature = 'classroom:prepare-test {--tutor-email= : Email of existing tutor}';

    protected $description = 'Создает тестового ученика и связывает его активным уроком с репетитором для проверки виртуального класса LiveKit';

    public function handle(LiveKitService $liveKitService): int
    {
        $this->info('🚀 Подготовка тестового окружения для виртуального класса Edusfera...');

        // 1. Поиск или создание репетитора
        $tutorEmail = $this->option('tutor-email');
        if ($tutorEmail) {
            $tutor = User::withTrashed()->where('email', $tutorEmail)->first();
            if (! $tutor) {
                $this->error("Репетитор с email {$tutorEmail} не найден.");

                return 1;
            }
            if ($tutor->trashed()) {
                $tutor->restore();
            }
        } else {
            $tutor = User::query()
                ->where('role', 'tutor')
                ->whereHas('tutorProfile')
                ->first();

            if (! $tutor) {
                $tutor = User::query()->where('role', 'tutor')->first();
            }

            if (! $tutor) {
                $this->warn('Репетитор не найден в базе, создаём демонстрационного...');
                $tutor = User::withTrashed()->where('email', 'tutor-demo@edusfera.by')->first();
                if ($tutor) {
                    if ($tutor->trashed()) {
                        $tutor->restore();
                    }
                } else {
                    $tutor = User::create([
                        'name' => 'Александр Репетитор',
                        'email' => 'tutor-demo@edusfera.by',
                        'password' => Hash::make('Edusfera2026!'),
                        'role' => 'tutor',
                        'phone' => '+375291112233',
                        'email_verified_at' => now(),
                    ]);
                }

                TutorProfile::firstOrCreate(
                    ['user_id' => $tutor->id],
                    [
                        'headline' => 'Преподаватель высшей категории',
                        'bio' => 'Подготовка к ЦТ, ЦЭ и олимпиадам.',
                        'hourly_rate' => 35.00,
                        'is_approved' => true,
                        'is_active' => true,
                    ]
                );
            }
        }

        // Гарантируем активную подписку репетитора (чтобы не сработал paywall)
        $sub = Subscription::where('tutor_id', $tutor->id)->first();
        if (! $sub) {
            $sub = new Subscription;
            $sub->tutor_id = $tutor->id;
        }
        $sub->plan = 'pro';
        $sub->status = 'active';
        $sub->is_onboarded = true;
        $sub->is_founder = true;
        $sub->current_period_starts_at = now()->subDays(5);
        $sub->current_period_ends_at = now()->addMonths(6);
        $sub->save();

        $this->info("✓ Репетитор: ID {$tutor->id} | {$tutor->name} ({$tutor->email})");

        // 2. Создание или обновление тестового ученика
        $studentPassword = 'Password123!';
        $student = User::withTrashed()->where('email', 'student-test@edusfera.by')->first();
        if ($student) {
            if ($student->trashed()) {
                $student->restore();
            }
        } else {
            $student = new User;
            $student->email = 'student-test@edusfera.by';
        }
        $student->name = 'Максим Ученик';
        $student->password = Hash::make($studentPassword);
        $student->role = 'student';
        $student->phone = '+375297778899';
        $student->email_verified_at = now();
        $student->save();

        $this->info("✓ Ученик: ID {$student->id} | {$student->name} ({$student->email})");

        // 3. Создание активного урока
        $startTime = Carbon::now()->subMinutes(5);
        $endTime = Carbon::now()->addHour();

        $lesson = Lesson::create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'status' => Lesson::STATUS_CONFIRMED,
            'payment_status' => 'paid',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => 60,
            'price' => 35.00,
            'platform_commission' => 0.00,
            'net_amount' => 35.00,
            'package_code' => 'single',
            'package_lessons' => 1,
            'notes' => 'Тестирование виртуального класса Edusfera LiveKit Cloud',
        ]);

        // 4. Инициализация сессии класса
        $roomId = (string) Str::uuid();
        $session = ClassroomSession::create([
            'lesson_id' => $lesson->id,
            'room_id' => $roomId,
            'status' => ClassroomSession::STATUS_ACTIVE,
        ]);

        $this->info("✓ Урок создан: ID {$lesson->id} | Сессия: {$session->room_id}");

        // 5. Проверка LiveKit Cloud
        $isLiveKitConfigured = $liveKitService->isConfigured();
        $this->newLine();
        $this->info('📡 <b>Диагностика LiveKit Cloud:</b>');
        $this->line('   - Статус конфигурации: '.($isLiveKitConfigured ? '<fg=green>НАСТРОЕН (Ключи найдены)</>' : '<fg=yellow>НЕ НАСТРОЕН (Будет использован P2P WebRTC)</>'));
        $this->line('   - WebSocket URL: '.$liveKitService->getWsUrl());

        if ($isLiveKitConfigured) {
            try {
                $tutorToken = $liveKitService->generateToken($lesson, $tutor);
                $studentToken = $liveKitService->generateToken($lesson, $student);
                $this->line('   - Токен Репетитора: <fg=green>Успешно сгенерирован ('.strlen($tutorToken).' байт)</>');
                $this->line('   - Токен Ученика:    <fg=green>Успешно сгенерирован ('.strlen($studentToken).' байт)</>');
            } catch (\Throwable $e) {
                $this->warn('   - Ошибка выпуска токена LiveKit: '.$e->getMessage());
            }
        }

        // 6. Вывод ссылок и доступов
        $baseUrl = config('app.url', 'https://edusfera.by');
        $classroomUrl = rtrim($baseUrl, '/')."/classroom/{$lesson->id}";
        $loginUrl = rtrim($baseUrl, '/').'/login';

        $this->newLine();
        $this->info('══════════════════════════════════════════════════════════════════');
        $this->info('🎉 ТЕСТОВЫЙ УРОК ГОТОВ К ЗАПУСКУ В ВИРТУАЛЬНОМ КЛАССЕ:');
        $this->info('══════════════════════════════════════════════════════════════════');
        $this->table(
            ['Параметр', 'Преподаватель (Tutor)', 'Ученик (Student)'],
            [
                ['Имя', $tutor->name, $student->name],
                ['Email / Логин', $tutor->email, $student->email],
                ['Пароль', '(текущий пароль репетитора)', $studentPassword],
                ['Роль в системе', 'tutor', 'student'],
                ['Страница входа', $loginUrl, $loginUrl],
                ['Прямая ссылка в класс', $classroomUrl, $classroomUrl],
            ]
        );

        $this->newLine();
        $this->line('👉 <b>Шаги для проверки в браузере:</b>');
        $this->line("1. В основном окне (или первом браузере) войдите под репетитором: <fg=cyan>{$tutor->email}</>");
        $this->line("2. Перейдите по ссылке класса: <fg=yellow>{$classroomUrl}</>");
        $this->line('3. В режиме инкогнито (или во втором браузере) войдите под учеником:');
        $this->line("   Email: <fg=cyan>{$student->email}</> | Пароль: <fg=cyan>{$studentPassword}</>");
        $this->line("4. Перейдите по той же ссылке класса: <fg=yellow>{$classroomUrl}</>");
        $this->line('5. Проверьте двустороннюю видеосвязь, звук, интерактивную доску и чат!');
        $this->newLine();

        return 0;
    }
}
