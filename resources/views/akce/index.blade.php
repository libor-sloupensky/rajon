<x-layouts.app title="Katalog akcí — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Katalog akcí</h1>
        <a href="{{ url('/mapa') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
            Zobrazit na mapě
        </a>
    </div>

    {{-- Filtry --}}
    <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Hledat</label>
            <input type="text" name="hledat" value="{{ request('hledat') }}" placeholder="Název akce..."
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Typ</label>
            <select name="typ" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Všechny</option>
                @foreach(['pout' => 'Pouť', 'food_festival' => 'Food festival', 'slavnosti' => 'Slavnosti', 'vinobrani' => 'Vinobraní', 'dynobrani' => 'Dýňobraní', 'farmarske_trhy' => 'Farmářské trhy', 'vanocni_trhy' => 'Vánoční trhy', 'jarmark' => 'Jarmark', 'festival' => 'Festival', 'jiny' => 'Jiný'] as $val => $label)
                    <option value="{{ $val }}" {{ request('typ') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kraj</label>
            <input type="text" name="kraj" value="{{ request('kraj') }}" placeholder="Kraj..."
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
        </div>
        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
            Filtrovat
        </button>
        @if(request()->hasAny(['hledat', 'typ', 'kraj', 'mesic', 'rok']))
            <a href="{{ url('/akce') }}" class="text-sm text-gray-500 hover:text-primary">Zrušit filtry</a>
        @endif
    </form>

    {{-- Seznam akcí --}}
    @if($akce->isEmpty())
        <p class="text-gray-500">Žádné akce nebyly nalezeny.</p>
    @else
        <div class="space-y-3">
            @foreach($akce as $a)
                <a href="{{ route('akce.show', $a) }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="font-medium text-gray-800">{{ $a->nazev }}</h2>
                            <p class="text-sm text-gray-500">{{ $a->misto }} {{ $a->okres ? '(' . $a->okres . ')' : '' }}</p>
                            @if($a->organizator)
                                <p class="text-xs text-gray-400 mt-1">Organizátor: {{ $a->organizator }}</p>
                            @endif
                        </div>
                        <div class="text-right text-sm shrink-0 ml-4">
                            <div class="font-medium" style="color: var(--c-primary);">
                                {{ $a->datum_od?->format('j. n. Y') }}
                                @if($a->datum_do && $a->datum_do->ne($a->datum_od))
                                    — {{ $a->datum_do->format('j. n.') }}
                                @endif
                            </div>
                            @if($a->typ !== 'jiny')
                                <span class="inline-block mt-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium" style="color: var(--c-primary);">{{ str_replace('_', ' ', $a->typ) }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $akce->withQueryString()->links() }}
        </div>
    @endif
</x-layouts.app>
