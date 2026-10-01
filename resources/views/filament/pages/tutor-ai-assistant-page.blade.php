<x-filament-panels::page>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])

    <script>
        window.EDUSFERA_AI_CONFIG = @json($this->getViewData());
    </script>

    <div id="tutor-ai-assistant-root" class="w-full"></div>
</x-filament-panels::page>
