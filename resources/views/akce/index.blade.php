<x-layouts.app title="Katalog akcí — Rajón">
    @php
        $jeAdmin = Auth::user()?->jeAdmin();
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Katalog akcí ({{ $akce->total() }})</h1>
        <div class="flex gap-2">
            <a href="{{ url('/mapa') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                Mapa
            </a>
            <a href="{{ route('akce.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                + Nová akce
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filtry --}}
    @php
        $kraje = [
            'Praha', 'Středočeský kraj', 'Jihočeský kraj', 'Plzeňský kraj',
            'Karlovarský kraj', 'Ústecký kraj', 'Liberecký kraj',
            'Královéhradecký kraj', 'Pardubický kraj', 'Kraj Vysočina',
            'Jihomoravský kraj', 'Olomoucký kraj', 'Zlínský kraj',
            'Moravskoslezský kraj',
        ];
        $typy = [
            'pout' => 'Pouť',
            'food_festival' => 'Food festival',
            'slavnosti' => 'Slavnosti a městské akce',
            'obrani' => 'Obraní',
            'trhy_jarmarky' => 'Trhy a jarmarky',
            'festival' => 'Festival',
            'sportovni_akce' => 'Sportovní akce',
            'koncert' => 'Koncert',
            'vystava' => 'Výstava',
            'jiny' => 'Jiný',
        ];
    @endphp
    <form method="GET" class="mb-6 space-y-2">
        {{-- Řádek 1: hledat (přes celou šířku) --}}
        <input type="text" name="hledat" value="{{ request('hledat') }}"
            placeholder="Hledat (název, místo, organizátor)…"
            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary focus:outline-none">

        {{-- Řádek 2: typ | kraj | datumy (od → do v jedné buňce) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
            <select name="typ" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                <option value="">Typ — všechny</option>
                @foreach($typy as $val => $label)
                    <option value="{{ $val }}" {{ request('typ') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="kraj" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                <option value="">Kraj — všechny</option>
                @foreach($kraje as $k)
                    <option value="{{ $k }}" {{ request('kraj') === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-1">
                <input type="date" name="datum_od" value="{{ request('datum_od') }}"
                    title="Datum od" aria-label="Datum od"
                    class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                <span class="text-gray-400 text-xs">–</span>
                <input type="date" name="datum_do" value="{{ request('datum_do') }}"
                    title="Datum do" aria-label="Datum do"
                    class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
            </div>
        </div>

        {{-- Řádek 3: stav (admin) | I minulé | tlačítka --}}
        <div class="flex flex-wrap items-center gap-3">
            @if($jeAdmin)
                <select name="stav" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                    <option value="">Stav — všechny (kromě zrušených)</option>
                    @foreach(['navrh' => 'Návrh', 'overena' => 'Ověřená', 'zrusena' => 'Zrušená'] as $v => $l)
                        <option value="{{ $v }}" {{ request('stav') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            @endif

            <label class="text-xs text-gray-500 flex items-center gap-1">
                <input type="checkbox" name="vse" value="1" {{ request('vse') ? 'checked' : '' }} class="rounded">
                I minulé
            </label>

            <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-dark transition">
                Filtrovat
            </button>
            @if(request()->hasAny(['hledat', 'typ', 'kraj', 'mesic', 'rok', 'datum_od', 'datum_do', 'stav', 'vse', 'zdroj_typ']))
                <a href="{{ url('/akce') }}" class="text-xs text-gray-500 hover:text-primary">Zrušit</a>
            @endif
        </div>
    </form>

    {{-- Seznam akcí --}}
    @if($akce->isEmpty())
        <p class="text-gray-500">Žádné akce nebyly nalezeny.</p>
    @else
        <div class="space-y-2">
            @foreach($akce as $a)
                <div class="rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <a href="{{ route('akce.show', $a) }}" class="font-medium text-gray-800 hover:text-primary">{{ $a->nazev }}</a>
                                @if($a->stav === 'zrusena')
                                    <span class="rounded-full bg-red-100 text-red-700 px-2 py-0.5 text-xs font-medium">zrušena</span>
                                @elseif($a->stav === 'navrh')
                                    <span class="rounded-full bg-yellow-100 text-yellow-700 px-2 py-0.5 text-xs font-medium">návrh</span>
                                @endif
                                @if($a->velikost_stav === 'ano')
                                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium" style="color: var(--c-primary);">velká akce ({{ $a->velikost_skore }})</span>
                                @elseif($a->velikost_stav === 'nejasna')
                                    <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">nejasná ({{ $a->velikost_skore }})</span>
                                @endif
                                @php
                                    // Filtrovat interní markery (klíče s '_' prefix)
                                    $manualniPole = array_filter(array_keys($a->pole_manualni ?? []), fn ($k) => !str_starts_with($k, '_'));
                                @endphp
                                @if(!empty($manualniPole))
                                    <span class="text-xs text-gray-500" title="Manuálně upravená pole: {{ implode(', ', $manualniPole) }}">🔒 {{ count($manualniPole) }}</span>
                                @endif
                                @if(!empty($a->konflikty))
                                    <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700" title="Konflikty mezi zdroji">⚠️ {{ count($a->konflikty) }}</span>
                                @endif
                                @if(!empty($a->navrh_propojeni))
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700" title="AI navrhlo propojení s jinou akcí">🔗 {{ count($a->navrh_propojeni) }}</span>
                                @endif
                                @if($a->typ && $a->typ !== 'jiny')
                                    @php
                                        $typLabel = ['pout' => 'pouť', 'food_festival' => 'food festival', 'slavnosti' => 'slavnosti / městské akce', 'obrani' => 'obraní', 'trhy_jarmarky' => 'trhy & jarmarky', 'festival' => 'festival', 'sportovni_akce' => 'sportovní akce', 'koncert' => 'koncert', 'divadlo' => 'divadlo', 'vystava' => 'výstava'][$a->typ] ?? str_replace('_', ' ', $a->typ);
                                    @endphp
                                    <span class="rounded-full bg-blue-100 text-blue-700 px-2 py-0.5 text-xs font-medium">{{ $typLabel }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">
                                {{ $a->datum_od?->format('j. n. Y') }}
                                @if($a->datum_do && $a->datum_do->ne($a->datum_od))
                                    — {{ $a->datum_do->format('j. n. Y') }}
                                @endif
                                · {{ $a->misto }}{{ $a->okres ? ' (' . $a->okres . ')' : '' }}
                                @if($a->kraj) · {{ $a->kraj }} @endif
                            </p>
                            @if($a->organizator)
                                <p class="text-xs text-gray-400 mt-0.5">Organizátor: {{ $a->organizator }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1 shrink-0">
                            <a href="{{ route('akce.edit', $a) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 text-center">Upravit</a>
                            <a href="{{ route('akce.show', $a) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-500 hover:bg-gray-50 text-center">Detail</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $akce->withQueryString()->links() }}
        </div>
    @endif
</x-layouts.app>
