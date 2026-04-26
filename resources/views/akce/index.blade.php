<x-layouts.app title="Katalog akcí — Rajón">
    @php
        $jeAdmin = Auth::user()?->jeAdmin();
        $maFiltr = request()->hasAny(['hledat', 'typ', 'kraj', 'mesic', 'rok', 'datum_od', 'datum_do', 'stav', 'vse', 'zdroj_typ']);
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            Katalog akcí
            <span class="text-base font-normal text-gray-500 ml-2">
                @if($maFiltr)
                    nalezeno {{ $akce->total() }}
                @else
                    celkem {{ $akce->total() }}
                @endif
            </span>
        </h1>
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
            'jiny' => 'Jiný',
        ];
        $stavy = ['navrh' => 'Návrh', 'overena' => 'Ověřená', 'zrusena' => 'Zrušená'];
    @endphp
    <form method="GET" class="mb-6 space-y-2">
        <input type="text" name="hledat" value="{{ request('hledat') }}"
            placeholder="Hledat (název, místo, organizátor)…"
            class="w-full rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-primary focus:outline-none">

        {{-- Řádek: Typ | Kraj | Stav (admin) --}}
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

            @if($jeAdmin)
                <select name="stav" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                    <option value="">Stav — všechny</option>
                    @foreach($stavy as $v => $l)
                        <option value="{{ $v }}" {{ request('stav') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            @else
                <div></div>
            @endif
        </div>

        {{-- Řádek: Datumy + minulé + Filtrovat --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1">
                <input type="date" name="datum_od" value="{{ request('datum_od') }}"
                    title="Datum od" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
                <span class="text-gray-400 text-xs">–</span>
                <input type="date" name="datum_do" value="{{ request('datum_do') }}"
                    title="Datum do" class="rounded-lg border border-gray-300 px-2 py-1.5 text-xs">
            </div>

            <label class="text-xs text-gray-500 flex items-center gap-1">
                <input type="checkbox" name="vse" value="1" {{ request('vse') ? 'checked' : '' }} class="rounded">
                I minulé
            </label>

            <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-dark transition">
                Filtrovat
            </button>
            @if($maFiltr)
                <a href="{{ url('/akce') }}" class="text-xs text-gray-500 hover:text-primary">Zrušit</a>
            @endif
        </div>
    </form>

    {{-- Seznam akcí --}}
    @if($akce->isEmpty())
        <p class="text-gray-500">Žádné akce nebyly nalezeny.</p>
    @else
        <div class="space-y-2" x-data>
            @foreach($akce as $a)
                @php
                    $mujPalec = $a->muj_palec ?? null;
                    $mojePoznamka = $a->moje_poznamka ?? '';
                    $manualniPole = array_filter(array_keys($a->pole_manualni ?? []), fn ($k) => !str_starts_with($k, '_'));
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition"
                     x-data="{
                        open: false,
                        palec: @js($mujPalec),
                        poznamka: @js($mojePoznamka),
                        async setPalec(novy) {
                            const value = this.palec === novy ? '' : novy;
                            this.palec = value || null;
                            const fd = new FormData();
                            if (value) fd.append('palec', value);
                            fd.append('_token', @js(csrf_token()));
                            await fetch('{{ route('akce.palec', $a) }}', { method: 'POST', body: fd });
                        },
                        async ulozPoznamku() {
                            const fd = new FormData();
                            fd.append('poznamka', this.poznamka);
                            fd.append('_token', @js(csrf_token()));
                            await fetch('{{ route('akce.poznamka', $a) }}', { method: 'POST', body: fd });
                        }
                     }">
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
                                @if(!empty($manualniPole))
                                    <span class="text-xs text-gray-500" title="Manuálně upravená pole: {{ implode(', ', $manualniPole) }}">🔒 {{ count($manualniPole) }}</span>
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
                            {{-- Osobní poznámka — viditelná v hlavním výpise --}}
                            <p x-show="poznamka" x-cloak class="text-xs text-purple-700 mt-1 italic" x-text="'📝 ' + poznamka"></p>
                        </div>

                        <div class="flex flex-col items-end gap-1 shrink-0">
                            {{-- Palec hodnocení --}}
                            <div class="flex gap-1">
                                <button type="button" @click="setPalec('nahoru')"
                                    :class="palec === 'nahoru' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-green-100'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center transition" title="Líbí se mi (nahoru v seznamu)">👍</button>
                                <button type="button" @click="setPalec('stred')"
                                    :class="palec === 'stred' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-orange-100'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center transition" title="Možná (níž v seznamu)">👉</button>
                                <button type="button" @click="setPalec('dolu')"
                                    :class="palec === 'dolu' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-400 hover:bg-red-100'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center transition" title="Nezajímá mě (dole)">👎</button>
                            </div>
                            <button type="button" @click="open = !open"
                                class="rounded-lg border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50">
                                <span x-text="open ? 'Skrýt detaily ▲' : 'Detaily ▼'"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Expandable detaily + inline edit --}}
                    <div x-show="open" x-cloak class="mt-3 pt-3 border-t border-gray-100">
                        @include('akce._detail_inline', ['a' => $a])

                        {{-- Osobní poznámka --}}
                        <div class="mt-3 rounded-lg border border-purple-200 bg-purple-50 p-2">
                            <label class="block text-xs font-medium text-purple-900 mb-1">📝 Moje poznámka (jen pro mě)</label>
                            <textarea x-model="poznamka" @blur="ulozPoznamku" rows="2"
                                class="w-full rounded border border-purple-300 bg-white px-2 py-1 text-sm"
                                placeholder="Soukromá poznámka — uložení automaticky po opuštění pole"></textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $akce->withQueryString()->links() }}
        </div>
    @endif

    <style>[x-cloak] { display: none !important; }</style>
</x-layouts.app>
