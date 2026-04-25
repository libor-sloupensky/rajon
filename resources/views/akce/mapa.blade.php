<x-layouts.app title="Mapa akcí — Rajón" :fullWidth="true">
    <div class="flex flex-col h-[calc(100vh-5rem)]">
        <div class="flex items-center justify-between px-4 py-2 bg-white border-b border-gray-200">
            <h1 class="text-lg font-bold text-gray-800">Mapa akcí</h1>
            <div class="text-sm text-gray-600">
                <span id="mapa-pocet">{{ count($akce) }}</span> akcí
                <a href="{{ url('/akce') }}" class="ml-4 text-primary hover:text-primary-dark">Seznam</a>
            </div>
        </div>

        <div id="mapa-kontejner" class="flex-1 relative bg-gray-100">
            <div id="mapa" class="w-full h-full"></div>
            <div id="mapa-loading" class="absolute inset-0 flex items-center justify-center bg-white/80 pointer-events-none">
                <p class="text-sm text-gray-500">Načítám mapu…</p>
            </div>
            @if (! config('services.mapycz.api_key'))
                <div class="absolute inset-0 flex items-center justify-center bg-white/95">
                    <div class="text-center">
                        <p class="text-sm text-gray-700 mb-2">Mapa není dostupná — chybí MAPYCZ_API_KEY</p>
                        <p class="text-xs text-gray-500">{{ count($akce) }} akcí by se zobrazilo na mapě</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (config('services.mapycz.api_key'))
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            (function () {
                var akceData = @json($akce);
                var apiKey = @json(config('services.mapycz.api_key'));

                var map = L.map('mapa', {
                    center: [49.7437, 15.3387], // střed ČR
                    zoom: 7,
                    minZoom: 6,
                    maxZoom: 18,
                });

                L.tileLayer('https://api.mapy.cz/v1/maptiles/basic/256/{z}/{x}/{y}?apikey=' + apiKey, {
                    attribution: '<a href="https://api.mapy.cz/copyright" target="_blank">&copy; Seznam.cz a.s. a další</a>',
                    tileSize: 256,
                }).addTo(map);

                L.control.attribution({ prefix: false }).addTo(map);

                var typBarvy = {
                    pout: '#dc2626',
                    food_festival: '#ea580c',
                    slavnosti: '#d97706',
                    vinobrani: '#7c2d12',
                    farmarske_trhy: '#65a30d',
                    vanocni_trhy: '#0891b2',
                    jarmark: '#9333ea',
                    festival: '#db2777',
                    sportovni_akce: '#2563eb',
                    jiny: '#6b7280',
                };

                var bounds = [];
                akceData.forEach(function (a) {
                    if (!a.gps_lat || !a.gps_lng) return;
                    var lat = parseFloat(a.gps_lat);
                    var lng = parseFloat(a.gps_lng);
                    if (isNaN(lat) || isNaN(lng)) return;

                    var barva = typBarvy[a.typ] || typBarvy.jiny;
                    var marker = L.circleMarker([lat, lng], {
                        radius: 7,
                        color: '#ffffff',
                        weight: 2,
                        fillColor: barva,
                        fillOpacity: 0.9,
                    }).addTo(map);

                    var datum = a.datum_od ? new Date(a.datum_od).toLocaleDateString('cs-CZ') : '';
                    var datumDo = a.datum_do && a.datum_do !== a.datum_od
                        ? ' – ' + new Date(a.datum_do).toLocaleDateString('cs-CZ')
                        : '';
                    var html = '<div class="text-sm">'
                        + '<strong>' + escapeHtml(a.nazev) + '</strong><br>'
                        + (a.misto ? escapeHtml(a.misto) + '<br>' : '')
                        + '<span class="text-gray-600">' + datum + datumDo + '</span>'
                        + '</div>';
                    marker.bindPopup(html);
                    bounds.push([lat, lng]);
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
                }

                // Schovat loading
                var loading = document.getElementById('mapa-loading');
                if (loading) loading.style.display = 'none';

                function escapeHtml(s) {
                    if (s == null) return '';
                    return String(s).replace(/[&<>"']/g, function (c) {
                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                    });
                }
            })();
        </script>
    @endif
</x-layouts.app>
