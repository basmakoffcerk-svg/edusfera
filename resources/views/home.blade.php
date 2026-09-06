<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera — Умная подготовка к ЦТ и ЦЭ с ИИ</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Silkscreen:wght@400;700&display=swap" rel="stylesheet">

    @php
        $authUser = auth()->user() ? [
            'id' => auth()->user()->id,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'role' => is_object(auth()->user()->role) ? auth()->user()->role->value : auth()->user()->role,
            'role_label' => \App\Services\MultiAccountService::roleLabel(auth()->user()->role),
        ] : null;
        $linkedAccounts = $authUser ? app(\App\Services\MultiAccountService::class)->getLinkedAccounts() : [];
    @endphp
    <script>
        window.EDUSFERA_USER = {!! json_encode($authUser) !!};
        window.EDUSFERA_LINKED_ACCOUNTS = {!! json_encode($linkedAccounts) !!};
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="nexum-body min-h-screen w-full antialiased font-geist bg-[#010101] text-white">
    <div id="app" class="w-full">
        {{-- SSR Initial Shell --}}
        <div class="w-full min-h-screen bg-[#010101] text-white flex flex-col justify-between p-8">
            <nav class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 text-white">
                    <span class="text-xl font-bold tracking-tight text-white font-rimma uppercase">edusfera</span>
                </a>
            </nav>
            <main class="max-w-xl my-auto">
                <h1 class="text-4xl font-semibold tracking-tight text-white">
                    Готовься к ЦТ и ЦЭ с ИИ-агентами, пока другие зубрят
                </h1>
            </main>
        </div>
    </div>
</body>
</html>