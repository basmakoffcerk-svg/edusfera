<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Виртуальный класс — Edusfera</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background:#f5f7fb; color:#111827; font-family: Inter, system-ui, sans-serif; }
        .wrap { max-width: 1200px; margin: 0 auto; padding: 20px; display:grid; gap:16px; }
        .top { display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; }
        .btn { display:inline-flex; align-items:center; min-height:42px; padding:0 16px; border-radius:10px; font-weight:700; text-decoration:none; }
        .btn-primary { background:#7d39eb; color:#fff; }
        .btn-muted { background:#e5e7eb; color:#111827; }
        .grid { display:grid; grid-template-columns: 2fr 1fr; gap:16px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; }
        .card h2 { margin:0; padding:14px 16px; border-bottom:1px solid #eef2f7; font-weight:800; font-size:16px; }
        iframe { width:100%; border:0; display:block; }
        .video { min-height:560px; }
        .board { min-height:320px; }
        .content { padding:14px 16px; }
        .meta { color:#4b5563; line-height:1.45; font-size:14px; }
        @media (max-width: 960px) { .grid { grid-template-columns: 1fr; } .video { min-height:440px; } }
    </style>
</head>
<body>
<main class="wrap">
    <div class="top">
        <div>
            <h1 style="font-size:24px;font-weight:900;margin:0;">Виртуальный класс</h1>
            <p class="meta" style="margin:4px 0 0;">
                Урок #{{ $lesson->id }} ·
                {{ $lesson->start_time->setTimezone(config('booking.display_timezone'))->format('d.m.Y H:i') }}
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ $meetingUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary">Открыть видео в отдельной вкладке</a>
            <a href="/admin/messages?conversation={{ optional($lesson->conversation)->id }}" class="btn btn-muted">Открыть чат</a>
        </div>
    </div>

    <div class="grid">
        <section class="card">
            <h2>Видеосессия</h2>
            <iframe class="video" src="{{ $meetingUrl }}" allow="camera; microphone; fullscreen; display-capture"></iframe>
        </section>
        <section class="card">
            <h2>Материалы урока</h2>
            <div class="content meta">
                <p><strong>Предмет:</strong> {{ $lesson->tutor?->tutorProfile?->subjects[0] ?? 'Урок' }}</p>
                <p><strong>Преподаватель:</strong> {{ $lesson->tutor?->name ?? '—' }}</p>
                <p><strong>Ученик:</strong> {{ $lesson->student?->name ?? '—' }}</p>
                <p><strong>Заметки:</strong> {{ $lesson->notes ?: 'Пока не добавлены.' }}</p>
            </div>
        </section>
    </div>

    <section class="card">
        <h2>Интерактивная доска</h2>
        <iframe class="board" src="https://excalidraw.com"></iframe>
    </section>
</main>
</body>
</html>
