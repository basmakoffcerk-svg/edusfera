<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Результат диагностики — Edusfera</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root { --ed-violet: #7D39EB; --ed-lime: #C6FF33; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f6f6f9; color: #0f1115; -webkit-font-smoothing: antialiased; }
        .font-rimma { font-family: 'Rimma Sans', 'Inter', system-ui, sans-serif; }
        .btn-core { display: inline-flex; align-items: center; justify-content: center; padding: 1.1rem 2.5rem; border-radius: 9999px; font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        .btn-violet { background: var(--ed-violet); color: white; box-shadow: 0 10px 30px rgba(125, 57, 235, 0.3); }
        .btn-violet:hover { transform: translateY(-2px); box-shadow: 0 15px 40px rgba(125, 57, 235, 0.4); }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center px-6 py-20">
        <a href="{{ route('home') }}" class="font-rimma font-bold text-2xl tracking-tighter flex items-center gap-2 mb-12">
            EDUSFERA
            <svg class="w-5 h-5 text-lime-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2L22 12L12 22L2 12L12 2Z" /></svg>
        </a>

        <div class="max-w-lg w-full text-center space-y-8">
            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <div class="space-y-3">
                <h1 class="text-3xl font-black tracking-[-0.04em]">Диагностика пройдена</h1>
                <p class="text-gray-500">Ваши данные сохранены и помогут подобрать оптимальную программу подготовки.</p>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-200 p-8 shadow-sm space-y-4 text-left">
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
                        <span class="font-bold text-violet-600">{{ $currentScore }} / 100</span>
                    </div>
                @endif
                @if($targetScore !== null)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Целевой балл</span>
                        <span class="font-bold">{{ $targetScore }} / 100</span>
                    </div>
                @endif
                @if(!empty($weakTopics))
                    <div class="pt-2 border-t border-gray-100">
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
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-sm text-amber-800 font-semibold">
                    Чтобы результат не потерялся и вы могли отслеживать прогресс — зарегистрируйтесь.
                </div>
            @endif

            <div class="flex flex-col sm:flex-row justify-center gap-4 pt-4">
                @if(!$isSaved)
                    <a href="/admin/register" class="btn-core btn-violet px-10">Зарегистрироваться</a>
                @else
                    <a href="/admin" class="btn-core btn-violet px-10">Перейти в кабинет</a>
                @endif
                <a href="/tutors?subject={{ urlencode($subject) }}&exam_type={{ urlencode($examType) }}&sort=match" class="btn-core bg-white text-gray-900 border border-gray-200 hover:border-gray-900 px-10">
                    Найти репетитора
                </a>
            </div>
        </div>
    </div>
</body>
</html>
