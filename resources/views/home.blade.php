<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edusfera — Умная подготовка к ЦТ и ЦЭ с ИИ | Платформа нового поколения</title>
    <meta name="description" content="Готовься к ЦТ и ЦЭ с персональными ИИ-агентами, интерактивной диагностикой знаний и проверенными репетиторами Беларуси. 100 баллов без зубрежки на платформе Edusfera.">
    <meta name="keywords" content="подготовка к ЦТ, подготовка к ЦЭ, репетиторы Беларусь, ИИ репетитор, диагностика знаний ЦТ, тесты РИКЗ, онлайн подготовка к экзаменам">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <link rel="canonical" href="https://edusfera.by/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="ru_BY">
    <meta property="og:site_name" content="Edusfera">
    <meta property="og:title" content="Edusfera — Умная подготовка к ЦТ и ЦЭ с ИИ | Платформа нового поколения">
    <meta property="og:description" content="Готовься к ЦТ и ЦЭ с персональными ИИ-агентами, интерактивной диагностикой знаний и проверенными репетиторами Беларуси.">
    <meta property="og:url" content="https://edusfera.by/">
    <meta property="og:image" content="https://edusfera.by/og-image.png">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Edusfera — Умная подготовка к ЦТ и ЦЭ с ИИ">
    <meta name="twitter:description" content="Готовься к ЦТ и ЦЭ с персональными ИИ-агентами и проверенными репетиторами.">
    <meta name="twitter:image" content="https://edusfera.by/og-image.png">

    <!-- Favicon & Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @include('partials.pwa-meta')
    @include('partials.analytics')

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "https://edusfera.by/#website",
          "url": "https://edusfera.by/",
          "name": "Edusfera",
          "description": "Платформа нового поколения для подготовки к ЦТ и ЦЭ в Беларуси",
          "inLanguage": "ru-BY",
          "potentialAction": {
            "@type": "SearchAction",
            "target": {
              "@type": "EntryPoint",
              "urlTemplate": "https://edusfera.by/catalog?search={search_term_string}"
            },
            "query-input": "required name=search_term_string"
          }
        },
        {
          "@type": "EducationalOrganization",
          "@id": "https://edusfera.by/#org",
          "name": "Edusfera",
          "alternateName": "ООО Эдусфера",
          "url": "https://edusfera.by/",
          "logo": "https://edusfera.by/favicon.svg",
          "image": "https://edusfera.by/og-image.png",
          "description": "Образовательная онлайн-платформа для подготовки к ЦТ и ЦЭ в Беларуси с проверенными репетиторами, ИИ-диагностикой и гарантией качества.",
          "address": {
            "@type": "PostalAddress",
            "addressCountry": "BY",
            "addressLocality": "Минск"
          },
          "contactPoint": {
            "@type": "ContactPoint",
            "email": "support@edusfera.by",
            "contactType": "customer service",
            "areaServed": "BY",
            "availableLanguage": ["Russian", "Belarusian"]
          }
        },
        {
          "@type": "SoftwareApplication",
          "@id": "https://edusfera.by/#app",
          "name": "Edusfera Platform 2.0",
          "applicationCategory": "EducationalApplication",
          "operatingSystem": "All",
          "url": "https://edusfera.by/",
          "description": "Интеллектуальная система адаптивной подготовки к экзаменам ЦТ/ЦЭ с поддержкой персональных ИИ-агентов, трекингом прогресса и виртуальным классом.",
          "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "BYN",
            "availability": "https://schema.org/InStock"
          }
        }
      ]
    }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Silkscreen:wght@400;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Inter:wght@400;500;600&family=Silkscreen:wght@400;700&display=swap" rel="stylesheet">
    </noscript>

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
        window.EDUSFERA_USER = @js($authUser);
        window.EDUSFERA_LINKED_ACCOUNTS = @js($linkedAccounts);
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
    @include('partials.pwa-prompt')
</body>
</html>