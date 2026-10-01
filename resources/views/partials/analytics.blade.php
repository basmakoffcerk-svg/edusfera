@php
    $ymId = config('services.analytics.yandex_metrika_id');
    $gaId = config('services.analytics.google_tag_id');
    $utmData = session('utm', []);
@endphp

<script>
    window.EDUSFERA_UTM = @js($utmData);
    window.EDUSFERA_ANALYTICS = {
        ymId: @js($ymId),
        gaId: @js($gaId)
    };

    /**
     * Unified event tracking function for EduSfera marketing funnel.
     * Dispatches to Yandex Metrika, Google Analytics 4, and GTM dataLayer.
     */
    window.trackEdusferaEvent = function(eventName, params = {}) {
        const enrichedParams = {
            ...params,
            utm_source: window.EDUSFERA_UTM?.source || undefined,
            utm_campaign: window.EDUSFERA_UTM?.campaign || undefined,
            timestamp: new Date().toISOString()
        };

        // 1. DataLayer
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: eventName,
            ...enrichedParams
        });

        // 2. Yandex Metrika Goal
        if (window.EDUSFERA_ANALYTICS.ymId && typeof window.ym === 'function') {
            try {
                window.ym(window.EDUSFERA_ANALYTICS.ymId, 'reachGoal', eventName, enrichedParams);
            } catch (err) {
                console.warn('[YM Error]', err);
            }
        }

        // 3. Google Analytics 4
        if (window.EDUSFERA_ANALYTICS.gaId && typeof window.gtag === 'function') {
            try {
                window.gtag('event', eventName, enrichedParams);
            } catch (err) {
                console.warn('[GA4 Error]', err);
            }
        }

        // 4. Dispatch browser custom event for internal listeners
        try {
            window.dispatchEvent(new CustomEvent('edusfera:analytics', {
                detail: { eventName, params: enrichedParams }
            }));
        } catch (_) {}

        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || !window.EDUSFERA_ANALYTICS.ymId) {
            console.log('%c[EduSfera Analytics] ' + eventName, 'color: #C6FF33; background: #111; padding: 2px 6px; border-radius: 4px;', enrichedParams);
        }
    };
</script>

@if(!empty($ymId))
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
       (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
       m[i].l=1*new Date();
       for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
       k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
       (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

       ym({{ $ymId }}, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
       });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ $ymId }}" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
@endif

@if(!empty($gaId))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ $gaId }}');
    </script>
@endif
