<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tutor->user->name }} — Edusfera</title>
    <meta name="description" content="Профиль репетитора {{ $tutor->user->name }} на Edusfera. Онлайн-запись и безопасная оплата через платформу.">
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
            --shadow-sm:0 1px 3px rgba(0,0,0,0.04);--shadow-md:0 4px 20px rgba(0,0,0,0.06);--shadow-lg:0 12px 40px rgba(0,0,0,.08);
            --font-display:'Space Grotesk',system-ui,sans-serif;
            --font-body:'Inter',system-ui,-apple-system,sans-serif;
        }
        html{scroll-behavior:smooth}
        body{font-family:var(--font-body);background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden}
        .container{max-width:1200px;margin:0 auto;padding:0 1.25rem}

        /* NAV */
        .ed-nav{display:flex;align-items:center;justify-content:space-between;padding:1.25rem 0}
        .ed-nav__brand{display:flex;align-items:center;gap:.6rem;text-decoration:none;color:var(--text)}
        .ed-nav__dot{width:8px;height:8px;border-radius:50%;background:var(--lime);box-shadow:0 0 12px var(--lime-glow)}
        .ed-nav__links{display:flex;align-items:center;gap:.25rem}
        .ed-nav__link{color:var(--text-sec);text-decoration:none;font-size:.9rem;font-weight:500;padding:.6rem 1rem;border-radius:.6rem;transition:color .2s,background .2s}
        .ed-nav__link:hover{color:var(--text);background:rgba(0,0,0,.04)}
        .ed-nav__btn{background:var(--violet);color:#fff;font-weight:600;padding:.6rem 1rem;border-radius:.6rem;text-decoration:none;font-size:.9rem;transition:background .2s}
        .ed-nav__btn:hover{background:#6A2ED1}
        .ed-nav__badge{background:#ef4444;color:#fff;font-size:.65rem;padding:1px 5px;border-radius:99px;margin-left:.3rem;font-weight:700}
        .ed-nav__dropdown{position:relative}
        .ed-nav__dropdown summary{list-style:none;cursor:pointer}
        .ed-nav__dropdown summary::-webkit-details-marker{display:none}
        .ed-nav__user{display:flex;align-items:center;gap:.5rem}
        .ed-nav__avatar{width:28px;height:28px;border-radius:50%;background:var(--lime);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#111}
        .ed-nav__menu{position:absolute;right:0;top:calc(100%+.5rem);width:13rem;background:#fff;border-radius:.75rem;box-shadow:0 12px 40px rgba(0,0,0,.15);padding:.4rem;z-index:30}
        .ed-nav__menu a,.ed-nav__menu button{display:block;width:100%;text-align:left;padding:.55rem .75rem;border:none;background:none;font-size:.88rem;font-weight:600;color:var(--text);border-radius:.5rem;text-decoration:none;cursor:pointer;transition:background .15s}
        .ed-nav__menu a:hover,.ed-nav__menu button:hover{background:#f3f4f6}
        .ed-nav__menu form button{color:#dc2626}
        .ed-nav__menu form button:hover{background:#fef2f2}
        .ed-nav__menu-meta{padding:.3rem .75rem .2rem;font-size:.7rem;color:#6b7280;text-transform:uppercase;letter-spacing:.05em}
        .ed-nav__menu-separator{height:1px;background:#e5e7eb;margin:.35rem 0}
        .ed-nav__linked-account{display:flex;align-items:center;gap:.5rem!important}
        .ed-nav__linked-avatar{width:24px;height:24px;border-radius:999px;background:rgba(125,57,235,.12);display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:var(--violet)}
        .ed-nav__linked-content{min-width:0;display:block}
        .ed-nav__linked-name{display:block;line-height:1.2;color:var(--text)}
        .ed-nav__linked-role{display:block;font-size:.7rem;line-height:1.2;color:#6b7280}
        .ed-nav__add-account{color:#365314!important}
        .ed-nav__add-account:hover{background:#f7fee7!important}

        .btn{display:inline-flex;align-items:center;justify-content:center;font-family:var(--font-body);font-weight:700;border-radius:.75rem;text-decoration:none;padding:.75rem 1.5rem;font-size:.95rem;transition:all .25s;cursor:pointer;border:none}
        .btn-primary{background:var(--lime);color:#111;box-shadow:0 0 16px var(--lime-glow)}
        .btn-primary:hover{background:var(--lime-hover);transform:translateY(-2px);box-shadow:0 0 24px var(--lime-glow)}
        .btn-primary:focus-visible{outline:none;box-shadow:0 0 0 3px #fff,0 0 0 5px #111}
        .btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
        .btn-outline:hover{border-color:var(--violet);transform:translateY(-2px)}
        .btn-violet{background:var(--violet);color:#fff}
        .btn-violet:hover{background:#6A2ED1;transform:translateY(-2px)}

        /* BACK */
        .tp-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--text-sec);text-decoration:none;font-weight:600;font-size:.9rem;padding:.75rem 0;transition:color .2s}
        .tp-back:hover{color:var(--text)}

        /* SUCCESS ALERT */
        .tp-success{padding:.85rem 1rem;border-radius:.75rem;background:#fefff5;border:1px solid rgba(198,255,51,.35);color:#365314;font-weight:700;font-size:.92rem;margin-bottom:1rem}

        /* LAYOUT */
        .tp-grid{display:grid;grid-template-columns:1fr 380px;gap:1.25rem;align-items:start}

        /* HERO CARD */
        .tp-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow-sm)}
        .tp-hero-grid{display:grid;grid-template-columns:200px 1fr;gap:1.5rem;align-items:start}
        .tp-photo{width:100%;aspect-ratio:1/1.15;border-radius:var(--radius-lg);background:linear-gradient(135deg,#f4f5f9,#eef3ff);overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:3.5rem;font-weight:900;color:var(--text)}
        .tp-photo img{width:100%;height:100%;object-fit:cover}
        .tp-badges{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.75rem}
        .tp-badge{display:inline-flex;align-items:center;min-height:1.7rem;padding:0 .7rem;border-radius:999px;font-size:.75rem;font-weight:700}
        .tp-badge.verify{background:rgba(198,255,51,.15);color:#365314}
        .tp-badge.safe{background:rgba(59,130,246,.1);color:#1d4ed8}

        .tp-name{font-family:var(--font-display);font-size:clamp(1.8rem,3.5vw,2.5rem);font-weight:800;letter-spacing:-.03em;line-height:1.1;margin-bottom:.5rem}
        .tp-spec{color:var(--text);font-size:1rem;font-weight:600;line-height:1.6;margin-bottom:.75rem}
        .tp-rating-row{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
        .tp-rating{font-size:.95rem;font-weight:800}
        .tp-rating-link{color:var(--violet);text-decoration:none;font-weight:700;font-size:.9rem}
        .tp-rating-link:hover{color:var(--text)}

        .tp-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-top:1.25rem}
        .tp-meta-card{padding:.85rem;border-radius:var(--radius);background:#f8f9fc}
        .tp-meta-card dt{color:var(--text-sec);font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em}
        .tp-meta-card dd{margin:.25rem 0 0;font-family:var(--font-display);font-weight:800;font-size:1.05rem}

        /* SIDEBAR */
        .tp-side{display:grid;gap:1rem;position:sticky;top:1rem}
        .tp-price-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--text-sec)}
        .tp-price{font-family:var(--font-display);font-size:2rem;font-weight:900;margin-top:.4rem;text-shadow:0 0 20px var(--lime-glow)}
        .tp-side-copy{color:var(--text-sec);font-size:.9rem;line-height:1.6;margin-top:.6rem}
        .tp-actions{display:grid;gap:.6rem;margin-top:1rem}
        .tp-actions .btn{width:100%}

        /* PACKAGES */
        .tp-packages{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-top:1rem}
        .tp-package{padding:1rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);display:flex;flex-direction:column;gap:.3rem}
        .tp-package.featured{border-width:2px;border-color:var(--lime);box-shadow:0 0 16px rgba(198,255,51,.12)}
        .tp-package-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-sec)}
        .tp-package h3{font-family:var(--font-display);font-size:1.1rem;font-weight:800;margin:0}
        .tp-package-price{font-family:var(--font-display);font-size:1.5rem;font-weight:900}
        .tp-package-note{color:var(--text-sec);font-size:.85rem;line-height:1.5;margin-top:auto}
        .tp-package-option{display:block;cursor:pointer}
        .tp-package-option input{position:absolute;opacity:0;pointer-events:none}
        .tp-package-option input:checked+.tp-package{border-color:var(--lime);background:#fefff5;box-shadow:0 0 20px rgba(198,255,51,.15)}

        /* SECTIONS */
        .tp-section{background:var(--card);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.75rem;box-shadow:var(--shadow-sm);margin-top:1.25rem}
        .tp-section h2{font-family:var(--font-display);font-size:clamp(1.3rem,2.5vw,1.7rem);font-weight:800;letter-spacing:-.02em;margin-bottom:.5rem}
        .tp-section p{color:var(--text-sec);line-height:1.7}

        /* DETAILS ACCORDION */
        .tp-details{display:grid;gap:.6rem;margin-top:1rem}
        .tp-details details{border:1px solid var(--border);border-radius:var(--radius);background:#f8f9fc;padding:.85rem 1rem;transition:border-color .2s}
        .tp-details details[open]{border-color:var(--violet)}
        .tp-details summary{cursor:pointer;font-weight:700;list-style:none;color:var(--text)}
        .tp-details summary::-webkit-details-marker{display:none}
        .tp-details .body{margin-top:.6rem;color:var(--text-sec);line-height:1.7}

        /* SLOTS  */
        .tp-slot-toolbar{display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem;align-items:end}
        .tp-slot-form{margin-top:1rem;display:grid;grid-template-columns:min(260px,100%) auto;gap:.75rem;align-items:end}
        .tp-input,.tp-textarea{width:100%;min-height:3rem;border-radius:.75rem;border:1px solid var(--border);background:var(--card);color:var(--text);padding:0 1rem;font-size:1rem;outline:none;transition:border-color .2s,box-shadow .2s}
        .tp-textarea{min-height:7rem;padding:.85rem 1rem;resize:vertical}
        .tp-input:focus,.tp-textarea:focus{border-color:var(--violet);box-shadow:0 0 0 3px var(--violet-light)}

        .tp-slots{display:grid;grid-template-columns:repeat(4,1fr);gap:.6rem;margin-top:1rem}
        .tp-slot{display:flex;min-height:3.2rem;align-items:center;justify-content:center;border-radius:.75rem;border:2px solid var(--border);background:var(--card);color:var(--text);font-weight:700;font-size:.88rem;transition:all .25s;cursor:pointer}
        .peer:checked+.tp-slot{border-color:var(--lime);background:#fefff5;box-shadow:0 0 16px rgba(198,255,51,.15)}
        .tp-slot:hover{transform:translateY(-2px);border-color:var(--lime)}
        .tp-package-progress{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-top:1rem;padding:1rem 1.25rem;border-radius:var(--radius);background:var(--violet);color:#fff;font-weight:800}
        .tp-package-progress strong{color:var(--lime);font-size:1.25rem}
        .tp-package-progress span:last-child{font-size:.88rem;font-weight:600;opacity:.9}
        .tp-package-help{margin-top:1rem;padding:1rem 1.25rem;border-radius:var(--radius);border:1px solid rgba(198,255,51,.35);background:#fefff5;color:var(--text-sec)}
        .tp-package-help strong{display:block;color:var(--text);margin-bottom:.25rem}

        .tp-booking-form{display:grid;gap:1rem;margin-top:1.25rem}
        .tp-booking-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
        .tp-form-label{display:block;margin-bottom:.4rem;font-size:.82rem;font-weight:700;color:var(--text-sec)}
        .tp-error{display:block;margin-top:.3rem;font-size:.8rem;color:#dc2626;font-weight:600}

        .tp-terms{display:flex;gap:.6rem;align-items:flex-start;padding:.85rem 1rem;border:1px solid var(--border);border-radius:var(--radius);background:#f8f9fc;font-size:.9rem;line-height:1.5;color:var(--text-sec)}
        .tp-terms input{margin-top:.15rem;accent-color:var(--violet)}

        .tp-review-empty{margin-top:1rem;padding:1.25rem;border:1px dashed var(--border);border-radius:var(--radius);background:#f8f9fc}
        .tp-review-empty strong{font-size:1rem;font-weight:800}
        .tp-review-empty span{display:block;margin-top:.35rem;color:var(--text-sec);line-height:1.6;font-size:.9rem}

        .tp-auth-box{margin-top:1rem;padding:1rem;border:1px solid var(--border);border-radius:var(--radius);background:#f8f9fc}
        .tp-auth-box p{margin:0 0 .75rem;font-size:.9rem;color:var(--text-sec)}
        .tp-auth-actions{display:flex;gap:.6rem;flex-wrap:wrap}

        /* STICKY BAR */
        .tp-sticky{display:none}
        .tp-sticky-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:1rem}
        .tp-sticky-price strong{font-family:var(--font-display);font-size:1.1rem;font-weight:900}
        .tp-sticky-price span{display:block;color:var(--text-sec);font-size:.8rem;font-weight:600}

        /* FOOTER */
        .ed-footer{border-top:1px solid var(--border);padding:2rem 0;margin-top:2.5rem}
        .ed-footer__inner{max-width:1200px;margin:0 auto;padding:0 1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
        .ed-footer__brand{display:flex;align-items:center;gap:.75rem}
        .ed-footer__copy{color:var(--text-sec);font-size:.85rem}
        .ed-footer__links{display:flex;gap:1.5rem}
        .ed-footer__links a{color:var(--text-sec);text-decoration:none;font-size:.85rem;font-weight:500;transition:color .2s}
        .ed-footer__links a:hover{color:var(--text)}

        #booking{scroll-margin-top:1rem}

        @media(max-width:960px){
            .tp-grid{grid-template-columns:1fr}
            .tp-side{position:static}
            .tp-hero-grid{grid-template-columns:1fr}
            .tp-meta{grid-template-columns:1fr}
            .tp-slots{grid-template-columns:repeat(2,1fr)}
            .tp-packages{grid-template-columns:repeat(2,1fr)}
            .tp-booking-grid,.tp-slot-form{grid-template-columns:1fr}
        }
        @media(max-width:640px){
            .tp-packages{grid-template-columns:1fr}
            .tp-slots{grid-template-columns:repeat(2,1fr)}
            .tp-photo{max-width:16rem}
            .tp-sticky{display:block;position:fixed;left:0;right:0;bottom:0;z-index:40;padding:.75rem 1rem calc(.75rem + env(safe-area-inset-bottom));background:rgba(255,255,255,.95);backdrop-filter:blur(16px);border-top:1px solid var(--border)}
            .container{padding-bottom:5rem}
            .ed-nav__link:not(.ed-nav__user){display:none}
            .ed-footer__inner{flex-direction:column;text-align:center}
            .ed-footer__links{justify-content:center}
        }
    </style>
</head>
<body>
    @php
        $singlePrice = (float) $tutor->price_per_hour;
        $pack4 = round($singlePrice * 4 * 0.95, 2);
        $pack8 = round($singlePrice * 8 * 0.90, 2);
        $pack4Saving = round(($singlePrice * 4) - $pack4, 2);
        $pack8Saving = round(($singlePrice * 8) - $pack8, 2);
        $requestedPackage = (string) request('package', old('package', 'single'));
        $selectedPackageCode = match ($requestedPackage) {
            '4', 'pack_4' => 'pack_4',
            '8', 'pack_8' => 'pack_8',
            default => 'single',
        };
        $selectedPackageLessons = match ($selectedPackageCode) {
            'pack_4' => 4,
            'pack_8' => 8,
            default => 1,
        };
        $isPackageBooking = $selectedPackageLessons > 1;
        $nameParts = collect(explode(' ', trim((string) $tutor->user->name)))->filter()->values();
        $maskedName = $nameParts->count() > 1
            ? $nameParts->first() . ' ' . mb_substr((string) $nameParts->get(1), 0, 1) . '.'
            : (string) $nameParts->first();
        $subjects = array_values(array_filter($tutor->subjects ?? []));
        $specialization = collect([
            $subjects ? implode(', ', array_slice($subjects, 0, 2)) : null,
            $tutor->experience_years > 0 ? "{$tutor->experience_years}+ лет опыта" : null,
            'Подготовка к ЦЭ / ЦТ и школе',
        ])->filter()->implode(' • ');
        $bioParts = collect(preg_split('/\n+|\.(?=\s|$)/u', (string) $tutor->bio))
            ->map(fn (?string $part) => trim((string) $part))
            ->filter()
            ->values();
        $approachText = trim((string) ($tutor->teaching_methodology ?: $bioParts->get(0) ?: 'Занятия проходят по заранее согласованному плану с понятными целями и контролем прогресса.'));
        $resultsText = trim((string) ($tutor->achievements ?: $bioParts->get(1) ?: 'Платформа покажет реальные результаты и отзывы после первых подтверждённых и оплаченных уроков.'));
        $educationText = trim((string) ($tutor->education_summary ?: $bioParts->slice(2)->implode('. ')));
        $educationText = $educationText !== '' ? rtrim($educationText, '.') . '.' : 'Репетитор заполняет этот блок при прохождении модерации.';
        $formats = collect($tutor->lesson_formats ?? [])->map(function (string $code): string {
            return match ($code) {
                'individual_online' => 'Индивидуально онлайн',
                'mini_group_online' => 'Мини-группа онлайн',
                'intensive' => 'Интенсив',
                'long_term' => 'Долгосрочная траектория',
                default => $code,
            };
        })->values()->all();
        $languages = collect($tutor->lesson_languages ?? [])->map(function (string $code): string {
            return match ($code) {
                'ru' => 'Русский',
                'be' => 'Белорусский',
                'en' => 'Английский',
                default => $code,
            };
        })->values()->all();
        $examSpecializations = collect($tutor->exam_specializations ?? [])->map(function (string $code): string {
            return match ($code) {
                'ЦЭ' => 'Подготовка к ЦЭ',
                'ЦТ' => 'Подготовка к ЦТ',
                'intensive' => 'Экзаменационный интенсив',
                'score_growth' => 'Рост балла за 8-12 недель',
                default => $code,
            };
        })->values()->all();
        $homeworkText = trim((string) ($tutor->homework_policy ?: 'Домашние задания и обратная связь обсуждаются индивидуально под цель ученика.'));
    @endphp

    <div class="container">
        @include('partials.site-nav')

        <a href="{{ route('tutors.index') }}" class="tp-back">← Назад к каталогу</a>

        @if(session('booking_success'))
            <div class="tp-success">{{ session('booking_success') }}</div>
        @endif

        <div class="tp-grid">
            <!-- MAIN -->
            <div>
                <article class="tp-card">
                    <div class="tp-hero-grid">
                        <div class="tp-photo" aria-hidden="true">
                            @if($tutor->avatar_path)
                                <img src="{{ asset('storage/'.$tutor->avatar_path) }}" alt="{{ $maskedName }}">
                            @else
                                {{ mb_substr($maskedName, 0, 1) }}
                            @endif
                        </div>
                        <div>
                            <div class="tp-badges">
                                <span class="tp-badge verify">✓ Диплом проверен</span>
                                <span class="tp-badge safe">🛡️ Безопасная оплата</span>
                            </div>
                            <h1 class="tp-name">{{ $maskedName }}</h1>
                            <p class="tp-spec">{{ $specialization }}</p>
                            <div class="tp-rating-row">
                                <div class="tp-rating">⭐ {{ number_format((float)$tutor->rating_avg, 1) }}</div>
                                <a href="#reviews" class="tp-rating-link">(проверенные отзывы)</a>
                            </div>
                            <dl class="tp-meta">
                                <div class="tp-meta-card">
                                    <dt>Стоимость</dt>
                                    <dd>{{ number_format($singlePrice, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/> / 60 мин</dd>
                                </div>
                                <div class="tp-meta-card">
                                    <dt>Формат</dt>
                                    <dd>Онлайн</dd>
                                </div>
                                <div class="tp-meta-card">
                                    <dt>Статус</dt>
                                    <dd>{{ match($tutor->legal_status) { 'npd'=>'НПД','ip'=>'ИП','self_employed'=>'Самозанятый',default=>'Частный' } }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </article>

                <!-- ABOUT -->
                <section class="tp-section">
                    <h2>О преподавателе</h2>
                    <div class="tp-details">
                        <details open>
                            <summary>Мой подход</summary>
                            <div class="body">{{ $approachText }}.</div>
                        </details>
                        <details>
                            <summary>Результаты учеников</summary>
                            <div class="body">{{ $resultsText }}</div>
                        </details>
                        <details>
                            <summary>Образование и профиль</summary>
                            <div class="body">{{ $educationText }}</div>
                        </details>
                        <details>
                            <summary>Форматы и язык занятий</summary>
                            <div class="body">
                                Форматы: {{ $formats !== [] ? implode(', ', $formats) : 'Индивидуально онлайн' }}.<br>
                                Язык: {{ $languages !== [] ? implode(', ', $languages) : 'Русский' }}.
                                @if($examSpecializations !== [])
                                    <br>Экзаменационные треки: {{ implode(', ', $examSpecializations) }}.
                                @endif
                                @if($tutor->trial_lesson_minutes)
                                    <br>Пробный созвон: {{ (int) $tutor->trial_lesson_minutes }} мин.
                                @endif
                            </div>
                        </details>
                        <details>
                            <summary>Подтверждённые результаты</summary>
                            <div class="body">
                                @if((int) $tutor->students_prepared_count > 0)
                                    Подготовлено учеников: {{ (int) $tutor->students_prepared_count }}.<br>
                                @endif
                                @if((int) $tutor->average_score_growth > 0)
                                    Средний рост результата: +{{ (int) $tutor->average_score_growth }} баллов.<br>
                                @endif
                                @if((int) $tutor->max_recent_score > 0)
                                    Лучший недавний результат: {{ (int) $tutor->max_recent_score }} баллов.<br>
                                @endif
                                @if($tutor->diagnostic_supported)
                                    Есть стартовая диагностика и работа по слабым темам.
                                @else
                                    Формат диагностики обсуждается индивидуально перед стартом.
                                @endif
                            </div>
                        </details>
                        <details>
                            <summary>Домашние задания и поддержка</summary>
                            <div class="body">{{ $homeworkText }}</div>
                        </details>
                    </div>
                </section>

                <!-- BOOKING -->
                <section id="booking" class="tp-section">
                    <div class="tp-slot-toolbar">
                        <div>
                            <h2>{{ $isPackageBooking ? "Бронирование пакета — {$selectedPackageLessons} занятий" : 'Свободное время на ближайшие дни' }}</h2>
                            <p>{{ $isPackageBooking ? "Выберите ровно {$selectedPackageLessons} слотов: вручную по дням или одним набором на текущей дате." : 'Выберите дату, нажмите на слот и зафиксируйте намерение.' }}</p>
                        </div>
                    </div>

                    @if($isPackageBooking)
                        <div class="tp-package-progress">
                            <span>Выбрано <strong data-selected-slots-count>0</strong> / {{ $selectedPackageLessons }} слотов</span>
                            <span>{{ $selectedPackageCode === 'pack_8' ? 'Скидка 10% уже учтена в итоговой цене' : 'Скидка 5% уже учтена в итоговой цене' }}</span>
                        </div>
                        <div class="tp-package-help">
                            <strong>Ручной выбор</strong>
                            Кликайте по слотам в сетке. Можно менять дату ниже: выбранные слоты сохранятся, пока страница открыта.
                        </div>
                    @endif

                    <form method="GET" action="{{ route('tutors.show', $tutor) }}" class="tp-slot-form">
                        <label>
                            <span class="tp-form-label">Дата занятия</span>
                            <input type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}"
                                   min="{{ app(\App\Services\BookingService::class)->minBookableDate()->format('Y-m-d') }}"
                                   class="tp-input">
                        </label>
                        @if($isPackageBooking)
                            <input type="hidden" name="package" value="{{ $selectedPackageLessons }}">
                        @endif
                        <button type="submit" class="btn btn-outline">Показать слоты</button>
                    </form>

                    @auth
                        @if(in_array(auth()->user()->role, ['student','parent'], true))
                            <form method="POST" action="{{ route('tutors.book', $tutor) }}" class="tp-booking-form" @if($isPackageBooking) data-package-booking data-required-slots="{{ $selectedPackageLessons }}" @endif>
                                @csrf
                                @if($errors->any())
                                    <div style="padding:.85rem 1rem;border-radius:.75rem;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;">
                                        <strong style="display:block;margin-bottom:.4rem;">Проверьте форму перед бронированием:</strong>
                                        <ul style="margin:0;padding-left:1rem;">
                                            @foreach($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                @error('slot')
                                    <span class="tp-error">{{ $message }}</span>
                                @enderror

                                <div class="tp-slots">
                                    @forelse($slots as $slot)
                                        <label style="cursor:pointer;">
                                            @if($isPackageBooking)
                                                <input type="checkbox" name="slots[]" value="{{ $slot['value'] }}" class="peer sr-only" @checked(in_array($slot['value'], old('slots', []), true))>
                                            @else
                                                <input type="radio" name="slot" value="{{ $slot['value'] }}" class="peer sr-only" required @checked(old('slot') === $slot['value'])>
                                            @endif
                                            <span class="tp-slot">{{ $selectedDate->translatedFormat('D') }} · {{ $slot['label'] }}</span>
                                        </label>
                                    @empty
                                        <div style="grid-column:1/-1;padding:1.5rem;border:1px dashed var(--border);border-radius:.75rem;background:#f8f9fc;color:var(--text-sec);font-size:.9rem;">
                                            На эту дату свободных слотов нет. Выберите другой день.
                                        </div>
                                    @endforelse
                                </div>

                                <div>
                                    <span class="tp-form-label">Пакет оплаты</span>
                                    <div class="tp-packages">
                                        <label class="tp-package-option">
                                            <input type="radio" name="package" value="single" @checked($selectedPackageCode === 'single')>
                                            <div class="tp-package">
                                                <div class="tp-package-label">Старт</div>
                                                <h3>Стартовая сессия</h3>
                                                <div class="tp-package-price">{{ number_format($singlePrice, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                                                <div class="tp-package-note">Диагностика, знакомство и фиксация цели.</div>
                                            </div>
                                        </label>
                                        <label class="tp-package-option">
                                            <input type="radio" name="package" value="pack_4" @checked($selectedPackageCode === 'pack_4')>
                                            <div class="tp-package featured">
                                                <div class="tp-package-label">Популярно</div>
                                                <h3>Траектория 4 занятия</h3>
                                                <div class="tp-package-price">{{ number_format($pack4, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                                                <div class="tp-package-note">Первая видимая траектория роста + экономия {{ number_format($pack4Saving, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/>.</div>
                                            </div>
                                        </label>
                                        <label class="tp-package-option">
                                            <input type="radio" name="package" value="pack_8" @checked($selectedPackageCode === 'pack_8')>
                                            <div class="tp-package">
                                                <div class="tp-package-label">Интенсив</div>
                                                <h3>Траектория 8 занятий</h3>
                                                <div class="tp-package-price">{{ number_format($pack8, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                                                <div class="tp-package-note">Полный цикл: домашка, отчёты и контроль прогресса. Экономия {{ number_format($pack8Saving, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/>.</div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="tp-booking-grid">
                                    <label>
                                        <span class="tp-form-label">Имя ученика</span>
                                        <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="tp-input" required>
                                        @error('name')<span class="tp-error">{{ $message }}</span>@enderror
                                    </label>
                                    <label>
                                        <span class="tp-form-label">Телефон</span>
                                        <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="+375XXXXXXXXX" class="tp-input" required pattern="\+375\d{9}" inputmode="numeric">
                                        @error('phone')<span class="tp-error">{{ $message }}</span>@enderror
                                    </label>
                                </div>

                                <label>
                                    <span class="tp-form-label">Комментарий к уроку</span>
                                    <textarea name="notes" rows="4" class="tp-textarea">{{ old('notes') }}</textarea>
                                    @error('notes')<span class="tp-error">{{ $message }}</span>@enderror
                                </label>

                                <label class="tp-terms">
                                    <input type="checkbox" name="terms" value="1" required @checked(old('terms'))>
                                    <span>Согласен с условиями отмены и переноса. Оплата и история брони остаются внутри платформы.</span>
                                </label>
                                @error('terms')<span class="tp-error">{{ $message }}</span>@enderror

                                <button type="submit" class="btn btn-primary">Забронировать</button>
                            </form>
                        @else
                            <div class="tp-auth-box"><p style="margin:0">Запись доступна из аккаунта ученика или родителя.</p></div>
                        @endif
                    @else
                        <div class="tp-auth-box">
                            <p>Чтобы зафиксировать время и безопасно оплатить урок, войдите или создайте аккаунт.</p>
                            <div class="tp-auth-actions">
                                <a href="/admin/login?redirect_to={{ urlencode(url()->full()) }}" class="btn btn-primary">Войти</a>
                                <a href="/admin/register?redirect_to={{ urlencode(url()->full()) }}" class="btn btn-outline">Регистрация</a>
                            </div>
                        </div>
                    @endauth
                </section>

                <!-- REVIEWS -->
                <section id="reviews" class="tp-section">
                    <h2>Отзывы</h2>
                    <p>Отзывы публикуются только после оплаченных уроков через Edusfera.</p>
                    <div class="tp-review-empty">
                        <strong>Пока нет опубликованных отзывов</strong>
                        <span>После первых проведённых и оплаченных уроков здесь появятся отзывы с пометкой «Ученик подтверждён платформой».</span>
                    </div>
                </section>
            </div>

            <!-- SIDEBAR -->
            <aside class="tp-side">
                <section class="tp-card">
                    <div class="tp-price-label">Первый урок</div>
                    <div class="tp-price">{{ number_format($singlePrice, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                    <p class="tp-side-copy">Оплата через платформу. Контакты открываются после первого бронирования.</p>
                    <div class="tp-actions">
                        <a href="#booking" class="btn btn-primary">Выбрать время</a>
                        @if($tutor->intro_video_url)
                            <a href="{{ $tutor->intro_video_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline">Видео-визитка</a>
                        @endif
                        @if (!empty($canStartConversation))
                            <form method="POST" action="{{ route('tutors.conversation', $tutor) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline" style="width:100%">Написать репетитору</button>
                            </form>
                        @endif
                    </div>
                </section>

                <section class="tp-card">
                    <div class="tp-price-label">Пакеты</div>
                    <div class="tp-packages">
                        <article class="tp-package">
                            <div class="tp-package-label">Базовый</div>
                            <h3>Стартовая сессия</h3>
                            <div class="tp-package-price">{{ number_format($singlePrice, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                            <div class="tp-package-note">Для знакомства и baseline.</div>
                        </article>
                        <article class="tp-package featured">
                            <div class="tp-package-label">Оптимально</div>
                            <h3>Траектория 4 занятия</h3>
                            <div class="tp-package-price">{{ number_format($pack4, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                            <div class="tp-package-note">Быстрый цикл роста и обратной связи.</div>
                        </article>
                        <article class="tp-package">
                            <div class="tp-package-label">Интенсив</div>
                            <h3>Траектория 8 занятий</h3>
                            <div class="tp-package-price">{{ number_format($pack8, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/></div>
                            <div class="tp-package-note">Серьёзная экзаменационная подготовка.</div>
                        </article>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    @include('partials.site-footer')

    <!-- STICKY BAR (mobile) -->
    <div class="tp-sticky">
        <div class="tp-sticky-inner">
            <div class="tp-sticky-price">
                <strong>{{ number_format($singlePrice, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/> / 60 мин</strong>
                <span>Пакеты и расписание ↑</span>
            </div>
            <a href="#booking" class="btn btn-primary">Записаться</a>
        </div>
    </div>
    <script>
        (() => {
            const packageForm = document.querySelector('[data-package-booking]');
            const packageInputs = document.querySelectorAll('input[type="radio"][name="package"]');

            packageInputs.forEach((input) => {
                input.addEventListener('change', () => {
                    const target = input.value === 'pack_8' ? '8' : (input.value === 'pack_4' ? '4' : null);

                    const url = new URL(window.location.href);
                    if (target) {
                        url.searchParams.set('package', target);
                    } else {
                        url.searchParams.delete('package');
                    }
                    window.location.href = url.toString();
                });
            });

            if (!packageForm) {
                return;
            }

            const requiredSlots = Number(packageForm.dataset.requiredSlots || 1);
            const counter = document.querySelector('[data-selected-slots-count]');
            const checkboxes = Array.from(packageForm.querySelectorAll('input[type="checkbox"][name="slots[]"]'));
            const storageKey = `edusfera.packageSlots:${window.location.pathname}:${requiredSlots}`;
            const selectedSlots = new Set(JSON.parse(window.localStorage.getItem(storageKey) || '[]'));

            const persist = () => {
                window.localStorage.setItem(storageKey, JSON.stringify(Array.from(selectedSlots)));
            };

            const render = () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectedSlots.has(checkbox.value);
                });

                if (counter) {
                    counter.textContent = String(selectedSlots.size);
                }
            };

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked && selectedSlots.size >= requiredSlots && !selectedSlots.has(checkbox.value)) {
                        checkbox.checked = false;
                        return;
                    }

                    if (checkbox.checked) {
                        selectedSlots.add(checkbox.value);
                    } else {
                        selectedSlots.delete(checkbox.value);
                    }

                    persist();
                    render();
                });
            });

            packageForm.addEventListener('submit', (event) => {
                packageForm.querySelectorAll('[data-generated-package-slot]').forEach((node) => node.remove());

                if (selectedSlots.size !== requiredSlots) {
                    event.preventDefault();
                    packageForm.reportValidity();
                    alert(`Выберите ровно ${requiredSlots} слотов для пакета.`);
                    return;
                }

                const visibleValues = new Set(checkboxes.map((checkbox) => checkbox.value));

                selectedSlots.forEach((slot) => {
                    if (visibleValues.has(slot)) {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'slots[]';
                    input.value = slot;
                    input.dataset.generatedPackageSlot = '1';
                    packageForm.appendChild(input);
                });
            });

            render();
        })();
    </script>
</body>
</html>
