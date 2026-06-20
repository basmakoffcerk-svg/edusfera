<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Бесплатная диагностика — Edusfera</title>
    <meta name="description" content="Определите свой уровень и получите индивидуальный план подготовки к ЦТ/ЦЭ.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --bg-color: #f6f6f9;
            --text-main: #0f1115;
            --text-muted: #6b7280;
            --ed-violet: #7D39EB;
            --ed-lime: #C6FF33;
        }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
            background-image: radial-gradient(circle at 50% 0%, rgba(125, 57, 235, 0.05) 0%, transparent 50%);
        }
        .font-rimma { font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif; }
        .btn-core {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 1.1rem 2.5rem; border-radius: 9999px; font-weight: 700;
            font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .btn-violet {
            background: var(--ed-violet); color: white;
            box-shadow: 0 10px 30px rgba(125, 57, 235, 0.3);
        }
        .btn-violet:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(125, 57, 235, 0.4); }
        .step-indicator { transition: all 0.3s ease; }
        .step-active { background: var(--ed-violet); color: white; }
        .step-done { background: #22c55e; color: white; }
        .step-pending { background: #e5e7eb; color: #9ca3af; }
        .option-card {
            border: 2px solid #e5e7eb; border-radius: 1.5rem; padding: 1.5rem;
            cursor: pointer; transition: all 0.2s ease; background: white;
        }
        .option-card:hover { border-color: #c4b5fd; background: #faf5ff; }
        .option-card.selected { border-color: var(--ed-violet); background: #f5f3ff; box-shadow: 0 0 0 4px rgba(125, 57, 235, 0.1); }
        .topic-chip {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.25rem; border-radius: 9999px; border: 2px solid #e5e7eb;
            font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s ease;
            background: white; user-select: none;
        }
        .topic-chip:hover { border-color: #c4b5fd; }
        .topic-chip.selected { border-color: var(--ed-violet); background: #f5f3ff; color: #6d28d9; }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col">
        {{-- Nav --}}
        <header class="py-6 px-6">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <a href="{{ route('home') }}" class="font-rimma font-bold text-2xl tracking-tighter flex items-center gap-2">
                    EDUSFERA
                    <svg class="w-5 h-5 text-lime-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2L22 12L12 22L2 12L12 2Z" /></svg>
                </a>
                @auth
                    <a href="/admin" class="text-sm font-bold text-gray-500 hover:text-black transition-colors">Кабинет →</a>
                @else
                    <a href="/admin/login" class="text-sm font-bold text-gray-500 hover:text-black transition-colors">Войти</a>
                @endauth
            </div>
        </header>

        {{-- Steps indicator --}}
        <div class="max-w-4xl mx-auto w-full px-6 mb-8">
            <div class="flex items-center justify-center gap-3">
                @foreach([1, 2, 3] as $s)
                    <div class="step-indicator w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold {{ $s < $step ? 'step-done' : ($s === $step ? 'step-active' : 'step-pending') }}">
                        @if($s < $step)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        @else
                            {{ $s }}
                        @endif
                    </div>
                    @if($s < 3)
                        <div class="h-0.5 w-12 {{ $s < $step ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="flex justify-center gap-16 mt-3 text-xs font-bold uppercase tracking-widest {{ $step === 1 ? 'text-violet-600' : ($step === 2 ? 'text-gray-400' : 'text-gray-400') }}">
                <span>Предмет</span>
                <span>Уровень</span>
                <span>Результат</span>
            </div>
        </div>

        {{-- Content --}}
        <main class="flex-1 max-w-4xl mx-auto w-full px-6 pb-20">
            {{-- Step 1: Subject + Exam --}}
            @if($step === 1)
                <div x-data="step1()" class="space-y-8">
                    <div class="text-center space-y-3">
                        <span class="inline-flex items-center rounded-full bg-violet-100 px-4 py-1 text-xs font-black uppercase tracking-[0.28em] text-violet-800">Шаг 1 из 3</span>
                        <h1 class="text-3xl md:text-4xl font-black tracking-[-0.04em]">Какой предмет и формат экзамена?</h1>
                        <p class="text-gray-500 max-w-xl mx-auto">Выберите предмет и тип экзамена, чтобы мы подобрали релевантные вопросы для диагностики.</p>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Предмет</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach($subjects as $key => $label)
                                <div class="option-card {{ $subject === $key ? 'selected' : '' }}"
                                     @click="selectedSubject = '{{ $key }}'"
                                     :class="{ 'selected': selectedSubject === '{{ $key }}' }">
                                    <div class="text-center">
                                        <div class="text-2xl mb-2">
                                            @if($key === 'Белорусский язык') 🇧🇾
                                            @elseif($key === 'Русский язык') 📖
                                            @else 🔢
                                            @endif
                                        </div>
                                        <div class="font-bold text-sm">{{ $label }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4">
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Тип экзамена</p>
                        <div class="flex gap-4">
                            @foreach($examTypes as $type)
                                <div class="option-card flex-1 {{ $examType === $type ? 'selected' : '' }}"
                                     @click="selectedExam = '{{ $type }}'"
                                     :class="{ 'selected': selectedExam === '{{ $type }}' }">
                                    <div class="text-center font-bold">{{ $type }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-center pt-4">
                        <button @click="submit()" :disabled="!selectedSubject || !selectedExam"
                                class="btn-core btn-violet px-12 disabled:opacity-40 disabled:cursor-not-allowed">
                            Далее
                        </button>
                    </div>
                </div>

            {{-- Step 2: Self-assessment --}}
            @elseif($step === 2)
                <div x-data="step2()" class="space-y-8">
                    <div class="text-center space-y-3">
                        <span class="inline-flex items-center rounded-full bg-violet-100 px-4 py-1 text-xs font-black uppercase tracking-[0.28em] text-violet-800">Шаг 2 из 3</span>
                        <h1 class="text-3xl md:text-4xl font-black tracking-[-0.04em]">Оцените свой уровень</h1>
                        <p class="text-gray-500 max-w-xl mx-auto">Честная самооценка поможет точнее подобрать программу подготовки.</p>
                    </div>

                    <div class="bg-white rounded-[2rem] border border-gray-200 p-6 md:p-8 space-y-6 shadow-sm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <label class="space-y-2">
                                <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Текущий ориентир (баллы из 100)</span>
                                <input type="number" min="0" max="100" x-model="currentScore"
                                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                       placeholder="Например, 42">
                            </label>
                            <label class="space-y-2">
                                <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Целевой балл</span>
                                <input type="number" min="0" max="100" x-model="targetScore"
                                       class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                       placeholder="Например, 75">
                            </label>
                        </div>

                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-500 mb-3">Какие темы даются тяжелее всего?</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($topicOptions as $topic)
                                    <span class="topic-chip"
                                          @click="toggleTopic('{{ $topic }}')"
                                          :class="{ 'selected': weakTopics.includes('{{ $topic }}') }">
                                        {{ $topic }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <label class="block space-y-2">
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-gray-500">Комментарий (необязательно)</span>
                            <textarea x-model="notes" rows="3"
                                      class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-100"
                                      placeholder="Что вызывает затруднения?"></textarea>
                        </label>
                    </div>

                    <div class="flex justify-center gap-4 pt-2">
                        <a href="{{ route('diagnostic.show') }}" class="btn-core bg-white text-gray-700 border border-gray-200 hover:border-gray-400 px-8">Назад</a>
                        <button @click="submit()" class="btn-core btn-violet px-12">Получить результат</button>
                    </div>
                </div>

            {{-- Step 3: Results --}}
            @elseif($step === 3)
                <div class="space-y-8 text-center">
                    <div class="space-y-3">
                        <span class="inline-flex items-center rounded-full bg-green-100 px-4 py-1 text-xs font-black uppercase tracking-[0.28em] text-green-800">Готово</span>
                        <h1 class="text-3xl md:text-4xl font-black tracking-[-0.04em]">Ваша диагностика сохранена</h1>
                    </div>

                    <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm max-w-lg mx-auto space-y-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Предмет</span>
                            <span class="font-bold">{{ $subject }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Экзамен</span>
                            <span class="font-bold">{{ $examType }}</span>
                        </div>
                        @if($currentScore !== null)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Текущий уровень</span>
                                <span class="font-bold">{{ $currentScore }} баллов</span>
                            </div>
                        @endif
                        @if($targetScore !== null)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Цель</span>
                                <span class="font-bold">{{ $targetScore }} баллов</span>
                            </div>
                        @endif
                        @if(!empty($weakTopics))
                            <div class="text-left">
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-gray-500 mb-2">Фокус подготовки</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($weakTopics as $topic)
                                        <span class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700">{{ $topic }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if(!$isSaved)
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 max-w-lg mx-auto">
                            <p class="text-sm font-semibold text-amber-800">Чтобы сохранить результат и начать подготовку, зарегистрируйтесь или войдите.</p>
                        </div>
                    @endif

                    <div class="flex flex-col sm:flex-row justify-center gap-4 pt-4">
                        @if(!$isSaved)
                            <a href="/admin/register" class="btn-core btn-violet px-10">Зарегистрироваться</a>
                        @endif
                        <a href="/tutors" class="btn-core bg-white text-gray-900 border border-gray-200 hover:border-gray-900 px-10">
                            Найти репетитора по {{ $subject }}
                        </a>
                    </div>
                </div>
            @endif
        </main>
    </div>

    <script>
        function step1() {
            return {
                selectedSubject: @json($subject),
                selectedExam: @json($examType),
                submit() {
                    fetch('{{ route("diagnostic.submit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            step: 1,
                            subject: [this.selectedSubject],
                            examType: this.selectedExam,
                        }),
                    }).then(r => r.json()).then(data => {
                        if (data.success) window.location.href = '{{ route("diagnostic.show") }}';
                    });
                }
            }
        }

        function step2() {
            return {
                currentScore: @json($currentScore),
                targetScore: @json($targetScore),
                weakTopics: @json($weakTopics),
                notes: @json($notes),
                toggleTopic(topic) {
                    const idx = this.weakTopics.indexOf(topic);
                    if (idx === -1) this.weakTopics.push(topic);
                    else this.weakTopics.splice(idx, 1);
                },
                submit() {
                    fetch('{{ route("diagnostic.submit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            step: 2,
                            currentScore: this.currentScore ? parseInt(this.currentScore) : null,
                            targetScore: this.targetScore ? parseInt(this.targetScore) : null,
                            weakTopics: this.weakTopics,
                            notes: this.notes,
                        }),
                    }).then(r => r.json()).then(data => {
                        if (data.success) window.location.href = '{{ route("diagnostic.finish") }}';
                    });
                }
            }
        }
    </script>
</body>
</html>
