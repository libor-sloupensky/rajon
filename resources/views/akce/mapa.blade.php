<x-layouts.app title="Mapa akcí — Rajón" :fullWidth="true">
    <div class="flex flex-col h-[calc(100vh-5rem)]">
        <div class="flex items-center justify-between px-4 py-2 bg-white border-b border-gray-200">
            <h1 class="text-lg font-bold text-gray-800">Mapa akcí</h1>
            <a href="{{ url('/akce') }}" class="text-sm text-primary hover:text-primary-dark">Zobrazit jako seznam</a>
        </div>

        <div id="mapa-kontejner" class="flex-1 relative bg-gray-100">
            <div id="mapa" class="w-full h-full"></div>
            <div id="mapa-loading" class="absolute inset-0 flex items-center justify-center bg-white/80">
                <p class="text-sm text-gray-500">Načítám mapu...</p>
            </div>
        </div>
    </div>

    <script>
        // Mapy.cz REST API integrace
        var akceData = @json($akce);

        document.addEventListener('DOMContentLoaded', function() {
            var loading = document.getElementById('mapa-loading');
            if (loading) {
                loading.innerHTML = '<div class="text-center"><p class="text-sm text-gray-500 mb-2">Mapa bude aktivní po nastavení MAPYCZ_API_KEY</p><p class="text-xs text-gray-400">' + akceData.length + ' akcí k zobrazení</p></div>';
            }
        });
    </script>
</x-layouts.app>
