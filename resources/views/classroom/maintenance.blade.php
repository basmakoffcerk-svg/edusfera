<!DOCTYPE html>
<html lang="ru" class="h-full bg-[#0C0A14] text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Виртуальный класс на обновлении — Edusfera</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0C0A14;
            color: #FFFFFF;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .cr-m-container {
            max-width: 680px;
            margin: 0 auto;
            width: 100%;
            padding: 32px 20px 64px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .cr-m-card {
            background: rgba(25, 20, 38, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            padding: 40px 32px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.5), 0 0 40px rgba(125, 57, 235, 0.15);
        }
        .cr-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(198, 255, 51, 0.1);
            border: 1px solid rgba(198, 255, 51, 0.3);
            color: #C6FF33;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 6px 14px;
            border-radius: 9999px;
            margin-bottom: 20px;
        }
        .cr-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #C6FF33;
            box-shadow: 0 0 10px #C6FF33;
        }
        .cr-title {
            font-size: clamp(26px, 4vw, 36px);
            font-weight: 900;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin-bottom: 14px;
        }
        .cr-desc {
            font-size: 15px;
            line-height: 1.6;
            color: #9CA3AF;
            margin-bottom: 28px;
        }
        .cr-lesson-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .cr-lesson-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 14px;
        }
        .cr-lesson-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .cr-lesson-label {
            color: #6B7280;
            font-weight: 500;
        }
        .cr-lesson-val {
            color: #FFFFFF;
            font-weight: 700;
            text-align: right;
        }
        .cr-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            box-sizing: border-box;
        }
        .cr-btn-primary {
            background: #7D39EB;
            color: #FFFFFF;
            box-shadow: 0 8px 24px rgba(125, 57, 235, 0.35);
        }
        .cr-btn-primary:hover {
            background: #6D28D9;
            transform: translateY(-1px);
        }
        .cr-btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #D1D5DB;
            margin-top: 10px;
        }
        .cr-btn-outline:hover {
            border-color: #FFFFFF;
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.05);
        }
        .cr-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 12px 16px;
            color: #FFFFFF;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
            margin-bottom: 12px;
        }
        .cr-input:focus {
            border-color: #7D39EB;
            box-shadow: 0 0 0 3px rgba(125, 57, 235, 0.25);
        }
        .cr-alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34D399;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header style="padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.06);">
        <a href="/" style="display: flex; align-items: center; gap: 8px; text-decoration: none; color: #FFFFFF;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C6FF33" stroke-width="2.5">
                <path d="M12 2L22 12L12 22L2 12L12 2Z" />
            </svg>
            <span style="font-weight: 900; font-size: 18px; letter-spacing: -0.03em; text-transform: uppercase;">Edusfera</span>
        </a>
        <a href="/admin" style="font-size: 13px; font-weight: 600; color: #9CA3AF; text-decoration: none; display: flex; align-items: center; gap: 6px;">
            В кабинет →
        </a>
    </header>

    <!-- Main -->
    <main class="cr-m-container">
        <div class="cr-m-card">
            <div class="cr-badge">
                <span class="cr-badge-dot"></span>
                Режим доработки платформы
            </div>

            <h1 class="cr-title">
                Виртуальный класс на обновлении
            </h1>

            <p class="cr-desc">
                Мы модернизируем интерактивный класс с доской и ИИ нового поколения. На время работ занятия проводятся через удобный для вас сервис (Zoom, Google Meet, Яндекс Телемост или Telegram).
            </p>

            @if(session('status'))
                <div class="cr-alert-success">
                    ✓ {{ session('status') }}
                </div>
            @endif

            <!-- Lesson Info -->
            <div class="cr-lesson-box">
                <div class="cr-lesson-row">
                    <span class="cr-lesson-label">Занятие:</span>
                    <span class="cr-lesson-val">{{ $lesson->subject ?? 'Индивидуальный урок' }}</span>
                </div>
                <div class="cr-lesson-row">
                    <span class="cr-lesson-label">Преподаватель:</span>
                    <span class="cr-lesson-val">{{ $lesson->tutor->name ?? 'Репетитор' }}</span>
                </div>
                <div class="cr-lesson-row">
                    <span class="cr-lesson-label">Ученик:</span>
                    <span class="cr-lesson-val">{{ $lesson->student->name ?? 'Ученик' }}</span>
                </div>
                <div class="cr-lesson-row">
                    <span class="cr-lesson-label">Время:</span>
                    <span class="cr-lesson-val">
                        {{ $lesson->start_time ? $lesson->start_time->translatedFormat('d F, H:i') : 'Сегодня' }}
                    </span>
                </div>
            </div>

            @if(!empty($lesson->meeting_link))
                <!-- Meeting link is available -->
                <div style="margin-bottom: 16px;">
                    <a href="{{ $lesson->meeting_link }}" target="_blank" rel="noopener noreferrer" class="cr-btn cr-btn-primary" data-haptic="success">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="23 7 16 12 23 17 23 7"></polygon>
                            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                        </svg>
                        Перейти к видеовстрече (Zoom / Meet) →
                    </a>
                </div>
                <p style="font-size: 12px; color: #6B7280; text-align: center; margin: 0 0 16px;">
                    Прямая ссылка: <a href="{{ $lesson->meeting_link }}" target="_blank" style="color: #9CA3AF; word-break: break-all;">{{ $lesson->meeting_link }}</a>
                </p>
            @else
                <!-- No meeting link yet -->
                @if(auth()->id() === $lesson->tutor_id || auth()->user()?->isAdmin())
                    <!-- Tutor can set link -->
                    <form method="POST" action="{{ route('classroom.meeting-link', $lesson) }}" style="margin-bottom: 20px;">
                        @csrf
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #D1D5DB; margin-bottom: 8px;">
                            Укажите ссылку на встречу (Zoom / Google Meet / Telegram):
                        </label>
                        <input type="url" name="meeting_link" class="cr-input" placeholder="https://meet.google.com/... или Zoom" required value="{{ old('meeting_link', $lesson->meeting_link) }}">
                        <button type="submit" class="cr-btn cr-btn-primary" data-haptic="tap">
                            Сохранить ссылку на урок
                        </button>
                    </form>
                @else
                    <!-- Student waiting for link -->
                    <div style="padding: 14px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px; color: #FBBF24; font-size: 14px; margin-bottom: 20px; text-align: center;">
                        Преподаватель пока не указал ссылку на видеовстречу. Напишите ему в чат, чтобы согласовать подключение.
                    </div>
                @endif
            @endif

            <div style="display: flex; gap: 10px; flex-direction: column;">
                <a href="/admin/messages" class="cr-btn cr-btn-outline" data-haptic="tap">
                    💬 Открыть чат урока в кабинете
                </a>
                <a href="/admin" class="cr-btn cr-btn-outline" style="border: none; color: #9CA3AF;" data-haptic="tap">
                    ← Вернуться в личный кабинет
                </a>
            </div>
        </div>
    </main>

    @include('partials.pwa-prompt')
</body>
</html>
