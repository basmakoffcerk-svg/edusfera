<!DOCTYPE html>
<html lang="ru" class="h-full w-full bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход и Регистрация — Edusfera</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script>
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
    </script>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="aurora-body bg-black text-white antialiased font-sans min-h-screen selection:bg-[#C6FF33] selection:text-black">
    <div id="aurora-auth" class="w-full min-h-screen"></div>
</body>
</html>
