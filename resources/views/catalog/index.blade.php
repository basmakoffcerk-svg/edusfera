<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталог репетиторов — Edusfera</title>
    <meta name="description" content="Подберите проверенного репетитора в Беларуси. Фильтр по предмету, цене и рейтингу. Безопасная оплата через платформу.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{
            --lime:#C6FF33;--lime-hover:#d8ff66;--lime-glow:rgba(198,255,51,0.35);
            --violet:#7D39EB;--violet-light:rgba(125,57,235,0.08);--violet-glow:rgba(125,57,235,0.25);
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
        .reveal{opacity:0;transform:translateY(24px);transition:opacity .6s cubic-bezier(.16,1,.3,1),transform .6s cubic-bezier(.16,1,.3,1)}
        .reveal.is-visible{opacity:1;transform:translateY(0)}

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

        /* HERO */
        .cat-hero{padding:2.5rem 0 1.5rem}
        .cat-hero h1{font-family:var(--font-display);font-size:clamp(1.8rem,3.5vw,2.5rem);font-weight:800;letter-spacing:-.03em;line-height:1.15;margin-bottom:.5rem}
        .cat-hero .lead{color:var(--text-sec);font-size:1.05rem;max-width:560px}

        /* SEARCH */
        .cat-search{display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap}
        .cat-search input[type=search]{flex:1;min-width:200px;min-height:3.2rem;border-radius:.75rem;border:1px solid var(--border);background:var(--card);padding:0 1rem;font-size:1rem;outline:none;transition:border-color .2s,box-shadow .2s}
        .cat-search input[type=search]:focus{border-color:var(--violet);box-shadow:0 0 0 3px var(--violet-light)}

        /* CHIPS */
        .cat-chips{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem}
        .cat-chip{display:inline-flex;align-items:center;min-height:2.25rem;padding:0 .85rem;border-radius:999px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:.88rem;font-weight:600;text-decoration:none;transition:border-color .2s,color .2s,transform .15s}
        .cat-chip:hover,.cat-chip.active{border-color:var(--violet);color:var(--violet);transform:translateY(-1px)}

        /* FILTERS */
        .cat-toolbar{margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;padding:1rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);box-shadow:var(--shadow-sm);align-items:end}
        .cat-filter{display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:140px}
        .cat-filter label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-sec)}
        .cat-filter select,.cat-filter input[type=number]{min-height:2.75rem;border-radius:.6rem;border:1px solid var(--border);background:var(--card);padding:0 .75rem;font-size:.92rem;outline:none;transition:border-color .2s}
        .cat-filter select:focus,.cat-filter input:focus{border-color:var(--violet)}
        .cat-checkbox{display:flex;align-items:center;gap:.5rem;min-height:2.75rem;padding:0 .75rem;border:1px solid var(--border);border-radius:.6rem;font-size:.9rem;font-weight:600;white-space:nowrap}
        .cat-checkbox input{accent-color:var(--violet)}

        .btn{display:inline-flex;align-items:center;justify-content:center;font-family:var(--font-body);font-weight:700;border-radius:.75rem;text-decoration:none;padding:.65rem 1.35rem;font-size:.92rem;transition:all .25s;cursor:pointer;border:none}
        .btn-primary{background:var(--lime);color:#111;box-shadow:0 0 16px var(--lime-glow)}
        .btn-primary:hover{background:var(--lime-hover);transform:translateY(-2px);box-shadow:0 0 24px var(--lime-glow)}
        .btn-outline{background:transparent;color:var(--text);border:1px solid var(--border)}
        .btn-outline:hover{border-color:var(--violet);transform:translateY(-2px)}
        .btn-violet{background:var(--violet);color:#fff}
        .btn-violet:hover{background:#6A2ED1;transform:translateY(-2px)}
        .btn-sm{padding:.5rem 1rem;font-size:.85rem}

        /* SORT BAR */
        .cat-sortbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;margin-top:1rem;padding:.85rem 1rem;border:1px solid var(--border);border-radius:var(--radius);background:var(--card);box-shadow:var(--shadow-sm);font-size:.92rem}
        .cat-sortbar .meta{color:var(--text-sec)}
        .cat-sortbar .actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}

        /* TUTOR CARDS */
        .cat-grid{display:grid;grid-template-columns:1fr;gap:1rem;margin-top:1.25rem}
        .cat-card{display:grid;grid-template-columns:6.5rem 1fr auto;gap:1.25rem;padding:1.25rem;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);box-shadow:var(--shadow-sm);transition:transform .3s,box-shadow .3s,border-color .3s;position:relative;align-items:start}
        .cat-card::before{content:'';position:absolute;inset:-1px;border-radius:inherit;padding:1.5px;background:linear-gradient(135deg,transparent,transparent);-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;transition:background .4s;pointer-events:none;z-index:1}
        .cat-card:hover::before{background:linear-gradient(135deg,var(--lime),var(--violet))}
        .cat-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);border-color:transparent}

        .cat-avatar{width:6.5rem;height:6.5rem;border-radius:var(--radius);background:linear-gradient(135deg,#f4f5f9,#eef3ff);display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:1.3rem;font-weight:800;color:var(--text)}
        .cat-avatar img{width:100%;height:100%;object-fit:cover}
        .cat-name{font-family:var(--font-display);font-size:1.2rem;font-weight:800;margin-bottom:.3rem}
        .cat-badges{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.5rem}
        .cat-badge{display:inline-flex;align-items:center;min-height:1.6rem;padding:0 .6rem;border-radius:999px;font-size:.72rem;font-weight:700}
        .cat-badge.official{background:rgba(198,255,51,.15);color:#365314}
        .cat-badge.top{background:rgba(250,204,21,.15);color:#854d0e}
        .cat-badge.new{background:var(--violet-light);color:var(--violet)}
        .cat-rating{font-size:.88rem;color:var(--text-sec);font-weight:600;margin-bottom:.5rem}
        .cat-meta{display:flex;gap:.4rem;flex-wrap:wrap}
        .cat-meta span{display:inline-flex;align-items:center;min-height:1.6rem;padding:0 .6rem;border-radius:999px;background:#f4f5f9;font-size:.78rem;font-weight:600;color:var(--text)}
        .cat-bio{color:var(--text-sec);font-size:.9rem;line-height:1.55;margin-top:.6rem}
        .cat-aside{display:flex;flex-direction:column;gap:.6rem;min-width:13rem}
        .cat-price{font-family:var(--font-display);font-size:1.3rem;font-weight:800;color:var(--text)}
        .cat-slot{padding:.5rem .7rem;border:1px dashed var(--border);border-radius:.6rem;background:#fefff5;color:#365314;font-size:.82rem;font-weight:700;line-height:1.4}
        .cat-card-actions{display:flex;flex-direction:column;gap:.5rem}
        .cat-card-actions .btn{width:100%}

        /* EMPTY & CTA */
        .cat-empty,.cat-cta{margin-top:1.5rem;padding:2.5rem;border:1px solid var(--border);border-radius:var(--radius-lg);background:var(--card);box-shadow:var(--shadow-sm);text-align:center}
        .cat-empty h2,.cat-cta h2{font-family:var(--font-display);font-size:1.4rem;font-weight:800;margin-bottom:.5rem}
        .cat-empty p,.cat-cta p{color:var(--text-sec);max-width:420px;margin:.5rem auto 1.5rem}
        .cat-empty .actions,.cat-cta .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}

        .cat-pagination{margin-top:1.5rem}
        .cat-pagination nav{display:flex;justify-content:center}

        /* FOOTER */
        .ed-footer{border-top:1px solid var(--border);padding:2rem 0;margin-top:3rem}
        .ed-footer__inner{max-width:1200px;margin:0 auto;padding:0 1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem}
        .ed-footer__brand{display:flex;align-items:center;gap:.75rem}
        .ed-footer__copy{color:var(--text-sec);font-size:.85rem}
        .ed-footer__links{display:flex;gap:1.5rem}
        .ed-footer__links a{color:var(--text-sec);text-decoration:none;font-size:.85rem;font-weight:500;transition:color .2s}
        .ed-footer__links a:hover{color:var(--text)}

        @media(max-width:960px){
            .cat-card{grid-template-columns:1fr}
            .cat-aside{flex-direction:row;flex-wrap:wrap;align-items:center;min-width:auto}
        }
        @media(max-width:640px){
            .cat-search{flex-direction:column}
            .cat-toolbar{flex-direction:column}
            .cat-sortbar{flex-direction:column;align-items:flex-start}
            .ed-nav__link:not(.ed-nav__user){display:none}
            .ed-footer__inner{flex-direction:column;text-align:center}
            .ed-footer__links{justify-content:center}
        }
    </style>
</head>
<body>
    <div class="container">
        @include('partials.site-nav')

        <!-- HERO -->
        <section class="cat-hero">
            <h1>Найдите репетитора и запишитесь онлайн</h1>
            <p class="lead">Фильтр по предмету, цене и рейтингу. Все анкеты проверены, оплата через платформу.</p>

            <form method="GET" action="{{ route('tutors.index') }}" class="cat-search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Поиск по имени преподавателя" aria-label="Поиск">
                @if(request()->filled('subject'))<input type="hidden" name="subject" value="{{ request('subject') }}">@endif
                @if(request()->filled('price_max'))<input type="hidden" name="price_max" value="{{ request('price_max') }}">@endif
                @if(request()->boolean('exam_track'))<input type="hidden" name="exam_track" value="1">@endif
                @if(request()->boolean('diagnostic_supported'))<input type="hidden" name="diagnostic_supported" value="1">@endif
                @if(request()->boolean('official'))<input type="hidden" name="official" value="1">@endif
                @if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                <button class="btn btn-primary" type="submit">Найти</button>
                <a class="btn btn-outline" href="{{ route('tutors.index') }}">Сбросить</a>
            </form>

            <div class="cat-chips" aria-label="Быстрый выбор предмета">
                @foreach(array_slice($allSubjects, 0, 6) as $subject)
                    <a href="{{ route('tutors.index', array_filter(['subject' => $subject, 'sort' => request('sort')])) }}"
                       class="cat-chip {{ request('subject') === $subject ? 'active' : '' }}">
                        {{ $subject }}
                    </a>
                @endforeach
            </div>
        </section>

        <!-- FILTERS -->
        <form method="GET" action="{{ route('tutors.index') }}" class="cat-toolbar">
            <div class="cat-filter">
                <label for="subject">Предмет</label>
                <select id="subject" name="subject">
                    <option value="">Все предметы</option>
                    @foreach($allSubjects as $subject)
                        <option value="{{ $subject }}" @selected(request('subject') === $subject)>{{ $subject }}</option>
                    @endforeach
                </select>
            </div>
            <div class="cat-filter">
                <label for="price_max">Макс. цена</label>
                <input id="price_max" type="number" min="0" step="1" name="price_max" value="{{ request('price_max') }}" placeholder="До &nbsp;&#8381;">
            </div>
            <div class="cat-filter">
                <label for="sort">Сортировка</label>
                <select id="sort" name="sort">
                    <option value="rating" @selected(request('sort','rating')==='rating')>По рейтингу</option>
                    <option value="price_asc" @selected(request('sort')==='price_asc')>Сначала дешевле</option>
                    <option value="price_desc" @selected(request('sort')==='price_desc')>Сначала дороже</option>
                    <option value="experience" @selected(request('sort')==='experience')>По опыту</option>
                    <option value="outcomes" @selected(request('sort')==='outcomes')>По результатам</option>
                </select>
            </div>
            <label class="cat-checkbox" for="exam_track">
                <input id="exam_track" type="checkbox" name="exam_track" value="1" @checked(request()->boolean('exam_track'))>
                Подготовка к ЦЭ/ЦТ
            </label>
            <label class="cat-checkbox" for="diagnostic_supported">
                <input id="diagnostic_supported" type="checkbox" name="diagnostic_supported" value="1" @checked(request()->boolean('diagnostic_supported'))>
                Есть диагностика
            </label>
            <label class="cat-checkbox" for="official">
                <input id="official" type="checkbox" name="official" value="1" @checked(request()->boolean('official'))>
                Только официальные
            </label>
            @if(request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            <button class="btn btn-primary btn-sm" type="submit">Применить</button>
            <a class="btn btn-outline btn-sm" href="{{ route('tutors.index') }}">Сбросить</a>
        </form>

        <!-- SORT BAR -->
        <div class="cat-sortbar">
            <div class="meta">Найдено <strong>{{ $tutors->total() }}</strong> анкет</div>
            <div class="actions">
                @auth
                    <a class="btn btn-outline btn-sm" href="/admin">Кабинет</a>
                    <a class="btn btn-violet btn-sm" href="/admin/messages">Сообщения</a>
                @else
                    <a class="btn btn-outline btn-sm" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Войти</a>
                    <a class="btn btn-primary btn-sm" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Стать репетитором</a>
                @endauth
            </div>
        </div>

        <!-- CARDS -->
        @if($tutors->isNotEmpty())
            <section class="cat-grid" aria-label="Список репетиторов">
                @foreach($tutors as $tutor)
                    @php
                        $subjects = array_values(array_filter($tutor->subjects ?? []));
                        $initials = collect(explode(' ', (string)$tutor->user?->name))
                            ->filter()->map(fn(string $p)=>mb_substr($p,0,1))->take(2)->implode('');
                        $maskedName = collect(explode(' ', trim((string)$tutor->user?->name)))
                            ->filter()->values()
                            ->pipe(function($parts){
                                if($parts->count()<=1) return (string)$parts->first();
                                return $parts->first().' '.mb_substr((string)$parts->get(1),0,1).'.';
                            });
                        $examSpecializations = array_values(array_filter($tutor->exam_specializations ?? []));
                        $isOfficial = $tutor->legal_status !== 'none';
                        $isTop = (float)$tutor->rating_avg >= 4.8;
                    @endphp

                    <article class="cat-card reveal">
                        <div class="cat-avatar" aria-hidden="true">
                            @if($tutor->avatar_path)
                                <img src="{{ asset('storage/'.$tutor->avatar_path) }}" alt="">
                            @else
                                {{ $initials ?: 'ED' }}
                            @endif
                        </div>

                        <div>
                            <div class="cat-badges">
                                @if($isOfficial)<span class="cat-badge official">Официальный</span>@endif
                                @if($tutor->diagnostic_supported)<span class="cat-badge new">Диагностика</span>@endif
                                @if(in_array('ЦЭ', $examSpecializations, true) || in_array('ЦТ', $examSpecializations, true))
                                    <span class="cat-badge top">ЦЭ/ЦТ</span>
                                @endif
                                @if((float)$tutor->rating_avg > 0)
                                    @if($isTop)<span class="cat-badge top">TOP</span>@endif
                                @else
                                    <span class="cat-badge new">Новый профиль</span>
                                @endif
                            </div>
                            <h2 class="cat-name">{{ $maskedName }}</h2>
                            @if((float)$tutor->rating_avg > 0)
                                <div class="cat-rating">⭐ {{ number_format((float)$tutor->rating_avg, 1) }} рейтинг</div>
                            @endif
                            <div class="cat-meta">
                                @foreach(array_slice($subjects,0,2) as $subject)
                                    <span>{{ $subject }}</span>
                                @endforeach
                                <span>{{ $tutor->experience_years }} лет опыта</span>
                            </div>
                            @if((int) $tutor->students_prepared_count > 0 || (int) $tutor->average_score_growth > 0 || (int) $tutor->max_recent_score > 0)
                                <div class="cat-meta" style="margin-top:.45rem;">
                                    @if((int) $tutor->students_prepared_count > 0)
                                        <span>{{ (int) $tutor->students_prepared_count }} учеников подготовлено</span>
                                    @endif
                                    @if((int) $tutor->average_score_growth > 0)
                                        <span>+{{ (int) $tutor->average_score_growth }} баллов в среднем</span>
                                    @endif
                                    @if((int) $tutor->max_recent_score > 0)
                                        <span>до {{ (int) $tutor->max_recent_score }} баллов</span>
                                    @endif
                                </div>
                            @endif
                            <p class="cat-bio">{{ \Illuminate\Support\Str::limit((string)$tutor->bio, 120, '...') }}</p>
                        </div>

                        <div class="cat-aside">
                            <div class="cat-price">{{ number_format((float)$tutor->price_per_hour, 2, '.', ' ') }}&nbsp;<x-byn-icon class="h-[0.9em] w-[0.9em] -mt-1"/>/час</div>
                            <div class="cat-slot">{{ $availabilityHints[$tutor->user_id] ?? 'Ближайшее окно уточняется' }}</div>
                            <div class="cat-card-actions">
                                <a class="btn btn-primary btn-sm" href="{{ route('tutors.show', $tutor) }}">Записаться</a>
                                <a class="btn btn-outline btn-sm" href="{{ route('tutors.show', $tutor) }}">Подробнее</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="cat-pagination">{{ $tutors->links() }}</div>
        @else
            <section class="cat-empty">
                <h2>По этим фильтрам пока ничего не найдено</h2>
                <p>Сбросьте фильтры, выберите другой предмет или расширьте диапазон цены.</p>
                <div class="actions">
                    <a class="btn btn-outline" href="{{ route('tutors.index') }}">Сбросить фильтры</a>
                    <a class="btn btn-primary" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Стать первым репетитором</a>
                </div>
            </section>
        @endif

        <!-- BOTTOM CTA -->
        <section class="cat-cta">
            <h2>Вы репетитор? Подключайтесь к Edusfera</h2>
            <p>Анкета, расписание, бронирование и оплата — в одном кабинете. Регистрация бесплатна.</p>
            <div class="actions">
                <a class="btn btn-primary" href="/admin/register?redirect_to={{ urlencode(url()->full()) }}">Зарегистрироваться бесплатно</a>
                <a class="btn btn-outline" href="/admin/login?redirect_to={{ urlencode(url()->full()) }}">Уже есть аккаунт</a>
            </div>
        </section>
    </div>

    @include('partials.site-footer')

    <script>
        document.addEventListener('DOMContentLoaded',()=>{
            const els=document.querySelectorAll('.reveal');
            if(!els.length)return;
            const io=new IntersectionObserver(entries=>{
                entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('is-visible');io.unobserve(e.target)}})
            },{threshold:.1,rootMargin:'0px 0px -30px 0px'});
            els.forEach(el=>io.observe(el));
        });
    </script>
</body>
</html>
