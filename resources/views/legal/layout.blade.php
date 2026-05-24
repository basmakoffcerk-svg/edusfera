<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Edusfera')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="ed-shell py-6 sm:py-8">
        @include('partials.public-header')

        <main class="mt-6 rounded-[2rem] border border-gray-200 bg-white px-6 py-8 shadow-sm sm:px-10 sm:py-12">
            <div class="mx-auto max-w-4xl">
                <div class="mb-8 border-b border-gray-100 pb-6">
                    <p class="mb-3 inline-flex rounded-full bg-[#F1E8FF] px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-[#7D39EB]">
                        Edusfera
                    </p>
                    <h1 class="text-3xl font-black tracking-tight text-gray-950 sm:text-5xl">@yield('heading')</h1>
                    <p class="mt-3 text-sm text-gray-500">Актуальная редакция: @yield('updated_at')</p>
                </div>

                <div class="prose prose-gray max-w-none prose-headings:font-black prose-a:text-[#7D39EB] prose-strong:text-gray-950">
                    @yield('content')
                </div>

                <div class="mt-10 rounded-3xl border border-gray-200 bg-gray-50 px-6 py-5">
                    <h2 class="text-lg font-black text-gray-950">Нужна помощь?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Если нужен ответ по бронированию, оплате или возврату, напишите на
                        <a class="font-semibold text-[#7D39EB]" href="mailto:{{ config('mail.from.address', 'support@edusfera.by') }}">
                            {{ config('mail.from.address', 'support@edusfera.by') }}
                        </a>
                        или перейдите на страницу
                        <a class="font-semibold text-[#7D39EB]" href="{{ route('contacts') }}">контактов</a>.
                    </p>
                </div>
            </div>
        </main>

        <div class="mt-6">
            @include('partials.site-footer')
        </div>
    </div>
</body>
</html>
