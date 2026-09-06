<div class="w-full min-h-screen bg-black">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <script>
        window.EDUSFERA_CSRF_TOKEN = "{{ csrf_token() }}";
    </script>
    <div id="aurora-auth" class="w-full min-h-screen"></div>
</div>

