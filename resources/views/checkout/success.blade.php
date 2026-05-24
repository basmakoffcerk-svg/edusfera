<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Урок оплачен — Edusfera</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --lime:#C6FF33;--lime-hover:#d8ff66;--lime-glow:rgba(198,255,51,0.35);
            --violet:#7D39EB;--violet-light:rgba(125,57,235,0.08);
            --dark:#0a0a0a;--text:#1a1a2e;--text-sec:#555770;
            --bg:#f8f9fc;--card:#fff;--border:#eaedf3;
            --radius:1rem;--radius-lg:1.5rem;
            --shadow-sm:0 1px 3px rgba(0,0,0,0.04);--shadow-md:0 4px 20px rgba(0,0,0,0.06);
            --font-display:'Space Grotesk',system-ui,sans-serif;
            --font-body:'Inter',system-ui,-apple-system,sans-serif;
        }
        html{scroll-behavior:smooth}
        body{font-family:var(--font-body);background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased}
        .container{max-width:1040px;margin:0 auto;padding:1.25rem}

        .ok-topbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.85rem 1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--card);box-shadow:var(--shadow-sm)}
        .ok-topbar .brand{text-decoration:none;color:var(--text)}

        .btn{display:inline-flex;align-items:center;justify-content:center;font-family:var(--font-body);font-weight:700;border-radius:.75rem;text-decoration:none;padding:.7rem 1.35rem;font-size:.92rem;transition:all .25s;cursor:pointer;border:none}
        .btn-primary{background:var(--lime);color:#111;box-shadow:0 0 16px var(--lime-glow)}
        .btn-primary:hover{background:var(--lime-hover);transform:translateY(-2px);box-shadow:0 0 24px var(--lime-glow)}
        .btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
        .btn-outline:hover{border-color:var(--violet);transform:translateY(-2px)}

        .ok-card{margin-top:1.25rem;padding:2rem;border-radius:var(--radius-lg);border:1px solid var(--border);background:var(--card);box-shadow:var(--shadow-md)}
        .ok-label{display:inline-flex;align-items:center;min-height:1.7rem;padding:0 .65rem;border-radius:999px;background:rgba(198,255,51,.15);color:#365314;font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}

        .ok-grid{display:grid;grid-template-columns:1fr 340px;gap:1.25rem;margin-top:1.25rem}
        .ok-hero h1{font-family:var(--font-display);font-size:clamp(1.8rem,3.5vw,2.6rem);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin:.75rem 0 0}
        .ok-copy{margin:.75rem 0 0;color:var(--text-sec);font-size:1rem;line-height:1.7;max-width:42rem}

        .ok-summary{padding:1.25rem;border-radius:var(--radius-lg);background:linear-gradient(180deg,#0c0c11,#16161f);color:#fff;border:none}
        .ok-summary small{display:block;color:rgba(255,255,255,.6);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .ok-summary strong{display:block;margin-top:.3rem;font-family:var(--font-display);font-size:1.4rem;font-weight:900}
        .ok-summary span{display:block;margin-top:.5rem;color:rgba(255,255,255,.7);line-height:1.5;font-size:.9rem}

        .ok-actions{padding:1.25rem;border-radius:var(--radius-lg);border:1px solid var(--border);background:var(--card);display:grid;gap:.6rem}
        .ok-actions .btn{width:100%}

        .ok-next{padding:1.25rem;border-radius:var(--radius-lg);border:1px solid var(--border);background:var(--card)}
        .ok-next h2{font-family:var(--font-display);font-size:1.1rem;font-weight:800;margin:0}
        .ok-list{margin:.75rem 0 0;padding:0;list-style:none;display:grid;gap:.6rem}
        .ok-list li{display:flex;gap:.6rem;align-items:flex-start;color:var(--text-sec);line-height:1.5;font-size:.9rem}
        .ok-list strong{color:var(--text)}

        .confetti{position:fixed;top:0;left:50%;width:4px;border-radius:2px;opacity:0;pointer-events:none;animation:confetti-fall 2.5s ease-out forwards}
        @keyframes confetti-fall{0%{opacity:1;transform:translateY(-20px) rotate(0deg) scale(1)}100%{opacity:0;transform:translateY(100vh) rotate(720deg) scale(0.3)}}

        /* FOOTER */
        .ed-footer{border-top:1px solid var(--border);padding:2rem 0;margin-top:3rem}
        .ed-footer__inner{max-width:1040px;margin:0 auto;padding:0 1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
        .ed-footer__brand{display:flex;align-items:center;gap:.75rem}
        .ed-footer__copy{color:var(--text-sec);font-size:.85rem}
        .ed-footer__links{display:flex;gap:1.5rem}
        .ed-footer__links a{color:var(--text-sec);text-decoration:none;font-size:.85rem;font-weight:500;transition:color .2s}
        .ed-footer__links a:hover{color:var(--text)}

        @media(max-width:860px){
            .ok-grid{grid-template-columns:1fr}
        }
        @media(max-width:640px){
            .container{padding:.85rem}
            .ok-card{padding:1.25rem}
            .ed-footer__inner{flex-direction:column;text-align:center}
            .ed-footer__links{justify-content:center}
        }
    </style>
</head>
<body>
    @php
        $tutorProfile = $lesson->tutor?->tutorProfile;
        $subjectLabel = $tutorProfile?->subjects[0] ?? 'Урок';
        $dateLabel = $lesson->start_time->setTimezone(config('booking.display_timezone'))->translatedFormat('l, d.m.Y \\в H:i');
    @endphp

    <main class="container">
        <div class="ok-topbar">
            <a href="{{ route('home') }}" class="brand ed-brand" style="font-size:1.6rem;">Edusfera</a>
            <a href="{{ route('tutors.show', $tutorProfile) }}" class="btn btn-outline">Вернуться к анкете</a>
        </div>

        <section class="ok-card">
            <span class="ok-label">Оплата подтверждена</span>

            <div class="ok-grid">
                <div class="ok-hero">
                    <h1>🎉 Оплата прошла, траектория подготовки запущена!</h1>
                    <p class="ok-copy">
                        {{ $subjectLabel }} · {{ $dateLabel }}. Контакты в чате уже открыты, слот закреплён за вами, а платформа создала учебную цель для дальнейшего прогресса.
                    </p>
                </div>
                <div class="ok-summary">
                    <small>Следующий шаг</small>
                    <strong>{{ $subjectLabel }}</strong>
                    <span>{{ $lesson->tutor?->name }} · {{ $dateLabel }} (Минск)</span>
                </div>
            </div>

            <div class="ok-grid" style="margin-top:1rem;">
                <div class="ok-actions">
                    @if($lesson->conversation)
                        <a href="{{ $chatUrl }}" class="btn btn-primary">Перейти в чат с преподавателем</a>
                    @endif
                    <a href="{{ $googleCalendarUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline">Добавить в Google Календарь</a>
                    <a href="{{ $calendarDownloadUrl }}" class="btn btn-outline">Скачать для Apple / Outlook</a>
                </div>
                <div class="ok-next">
                    <h2>Что делать дальше</h2>
                    <ul class="ok-list">
                        <li><span>1.</span><span><strong>Откройте чат.</strong> Согласуйте детали и формат первого занятия с преподавателем.</span></li>
                        <li><span>2.</span><span><strong>Пройдите диагностику.</strong> В кабинете появится baseline и слабые темы.</span></li>
                        <li><span>3.</span><span><strong>Следите за прогрессом.</strong> После уроков здесь появятся домашка, отчёты и движение к цели.</span></li>
                    </ul>
                </div>
            </div>
        </section>
    </main>

    @include('partials.site-footer')

    <!-- Confetti effect -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const colors = ['#C6FF33', '#7D39EB', '#facc15', '#38bdf8', '#f87171'];
            for (let i = 0; i < 30; i++) {
                const el = document.createElement('div');
                el.className = 'confetti';
                el.style.left = Math.random() * 100 + '%';
                el.style.width = (3 + Math.random() * 4) + 'px';
                el.style.height = (8 + Math.random() * 12) + 'px';
                el.style.background = colors[Math.floor(Math.random() * colors.length)];
                el.style.animationDelay = Math.random() * 1.5 + 's';
                el.style.animationDuration = (2 + Math.random() * 2) + 's';
                document.body.appendChild(el);
            }
            setTimeout(() => {
                document.querySelectorAll('.confetti').forEach(e => e.remove());
            }, 5000);
        });
    </script>
</body>
</html>
