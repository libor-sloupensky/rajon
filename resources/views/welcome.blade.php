<x-layouts.app title="Rajón — katalog akcí">
    <div class="text-center py-16">
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Rajón</h1>
        <p class="text-lg text-gray-600 mb-8">Katalog akcí a festivalů pro franšízanty WormUP</p>

        <div class="flex justify-center gap-4">
            <a href="{{ url('/akce') }}" class="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Prohlédnout akce
            </a>
            <a href="{{ url('/mapa') }}" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Otevřít mapu
            </a>
        </div>
    </div>
</x-layouts.app>
