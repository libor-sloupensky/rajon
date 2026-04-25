<x-layouts.app title="{{ $akce->nazev }} — Rajón">
    <div class="max-w-3xl">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ url('/akce') }}" class="text-sm text-primary hover:text-primary-dark inline-block">&larr; Zpět na katalog</a>
            @auth
                <a href="{{ route('akce.edit', $akce) }}" class="rounded-lg border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Upravit
                </a>
            @endauth
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $akce->nazev }}</h1>

        <div class="flex flex-wrap gap-3 mb-6">
            @if($akce->typ !== 'jiny')
                @php
                    $typLabel = ['pout' => 'pouť', 'food_festival' => 'food festival', 'slavnosti' => 'slavnosti / městské akce', 'obrani' => 'obraní', 'trhy_jarmarky' => 'trhy & jarmarky', 'festival' => 'festival', 'sportovni_akce' => 'sportovní akce', 'koncert' => 'koncert', 'divadlo' => 'divadlo', 'vystava' => 'výstava'][$akce->typ] ?? str_replace('_', ' ', $akce->typ);
                @endphp
                <span class="rounded-full bg-blue-100 text-blue-700 px-3 py-1 text-sm font-medium">{{ $typLabel }}</span>
            @endif
            <span class="rounded-full bg-gray-100 px-3 py-1 text-sm text-gray-600">
                {{ $akce->datum_od?->format('j. n. Y') }}
                @if($akce->datum_do && $akce->datum_do->ne($akce->datum_od))
                    — {{ $akce->datum_do->format('j. n. Y') }}
                @endif
            </span>
            @if($akce->stav === 'navrh')
                <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm text-yellow-700">Neověřená</span>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 space-y-4">
            @if($akce->popis)
                <div class="prose max-w-none">
                    {!! nl2br(e($akce->popis)) !!}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                @if($akce->misto)
                    <div>
                        <span class="font-medium text-gray-700">Místo:</span>
                        <span class="text-gray-600">{{ $akce->misto }}</span>
                    </div>
                @endif
                @if($akce->adresa)
                    <div>
                        <span class="font-medium text-gray-700">Adresa:</span>
                        <span class="text-gray-600">{{ $akce->adresa }}</span>
                    </div>
                @endif
                @if($akce->okres)
                    <div>
                        <span class="font-medium text-gray-700">Okres:</span>
                        <span class="text-gray-600">{{ $akce->okres }}</span>
                    </div>
                @endif
                @if($akce->kraj)
                    <div>
                        <span class="font-medium text-gray-700">Kraj:</span>
                        <span class="text-gray-600">{{ $akce->kraj }}</span>
                    </div>
                @endif
                @if($akce->organizator)
                    <div>
                        <span class="font-medium text-gray-700">Organizátor:</span>
                        <span class="text-gray-600">{{ $akce->organizator }}</span>
                    </div>
                @endif
                @if($akce->kontakt_email)
                    <div>
                        <span class="font-medium text-gray-700">E-mail:</span>
                        <a href="mailto:{{ $akce->kontakt_email }}" class="text-primary">{{ $akce->kontakt_email }}</a>
                    </div>
                @endif
                @if($akce->kontakt_telefon)
                    <div>
                        <span class="font-medium text-gray-700">Telefon:</span>
                        <a href="tel:{{ $akce->kontakt_telefon }}" class="text-primary">{{ $akce->kontakt_telefon }}</a>
                    </div>
                @endif
                @if($akce->web_url)
                    <div>
                        <span class="font-medium text-gray-700">Web:</span>
                        <a href="{{ $akce->web_url }}" target="_blank" rel="noopener" class="text-primary">{{ parse_url($akce->web_url, PHP_URL_HOST) }}</a>
                    </div>
                @endif
                @if($akce->najem)
                    <div>
                        <span class="font-medium text-gray-700">Nájem:</span>
                        <span class="text-gray-600">{{ number_format($akce->najem, 0, ',', ' ') }} Kč</span>
                    </div>
                @endif
                @if($akce->obrat)
                    <div>
                        <span class="font-medium text-gray-700">Obrat:</span>
                        <span class="text-gray-600">{{ number_format($akce->obrat, 0, ',', ' ') }} Kč</span>
                    </div>
                @endif
            </div>

            {{-- Mapa --}}
            @if($akce->gps_lat && $akce->gps_lng)
                <div id="mapa" class="w-full h-80 rounded-lg border border-gray-200 mt-4 overflow-hidden"></div>
            @endif
        </div>

        {{-- Rezervace --}}
        @auth
            <div class="mt-6">
                @if($rezervace)
                    <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">
                        Tato akce je ve vašem kalendáři (stav: {{ $rezervace->stav }}).
                    </div>
                @else
                    <form method="POST" action="{{ route('akce.rezervovat', $akce) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                            Přidat do mého kalendáře
                        </button>
                    </form>
                @endif
            </div>
        @else
            <p class="mt-6 text-sm text-gray-500">
                <a href="{{ route('login') }}" class="text-primary font-medium">Přihlaste se</a> pro přidání akce do kalendáře.
            </p>
        @endauth
    </div>

    @if($akce->gps_lat && $akce->gps_lng)
        @php $mapyKey = config('services.mapycz.api_key'); @endphp
        @if($mapyKey)
            {{-- Mapy.cz v1 přes Leaflet (oficiální postup) --}}
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
                integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var lat = {{ $akce->gps_lat }};
                    var lng = {{ $akce->gps_lng }};
                    var map = L.map('mapa').setView([lat, lng], 14);

                    L.tileLayer('https://api.mapy.cz/v1/maptiles/basic/256/{z}/{x}/{y}?apikey={{ $mapyKey }}', {
                        attribution: '<a href="https://api.mapy.cz/copyright" target="_blank">&copy; Seznam.cz a.s. a další</a>',
                        maxZoom: 19,
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map)
                        .bindPopup(@json($akce->nazev));
                });
            </script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('mapa').innerHTML =
                        '<div class="flex items-center justify-center h-full text-sm text-gray-400">' +
                        'GPS: {{ $akce->gps_lat }}, {{ $akce->gps_lng }} (Mapy.cz API klíč není nastaven)</div>';
                });
            </script>
        @endif
    @endif
</x-layouts.app>
