<x-layouts.app title="Katalog akcí — Rajón">
    @php
        $jeAdmin = Auth::user()?->jeAdmin();
        $maFiltr = request()->hasAny(['hledat', 'typ', 'kraj', 'mesic', 'rok', 'datum_od', 'datum_do', 'stav', 'vse', 'zdroj_typ', 'moje_rezervovane', 'radius']);
        $u = Auth::user();
        $userLat = $u?->gps_lat;
        $userLng = $u?->gps_lng;
    @endphp

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Katalog akcí</h1>
        <a href="{{ route('akce.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
            + Nová akce
        </a>
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

        {{-- Řádek: Typ | Kraj | Stav — vždy na 1 řádku, kompaktní --}}
        <div class="flex flex-wrap gap-2">
            <select name="typ" class="rounded border border-gray-300 px-2 py-1 text-xs flex-1 min-w-0">
                <option value="">Typ</option>
                @foreach($typy as $val => $label)
                    <option value="{{ $val }}" {{ request('typ') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="kraj" class="rounded border border-gray-300 px-2 py-1 text-xs flex-1 min-w-0">
                <option value="">Kraj</option>
                @foreach($kraje as $k)
                    <option value="{{ $k }}" {{ request('kraj') === $k ? 'selected' : '' }}>{{ $k }}</option>
                @endforeach
            </select>

            @if($jeAdmin)
                <select name="stav" class="rounded border border-gray-300 px-2 py-1 text-xs flex-1 min-w-0">
                    <option value="">Stav</option>
                    @foreach($stavy as $v => $l)
                        <option value="{{ $v }}" {{ request('stav') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Řádek: Datumy + checkboxy + Filtrovat --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1">
                <input type="date" name="datum_od" value="{{ request('datum_od') }}"
                    title="Datum od" class="rounded border border-gray-300 px-2 py-1 text-xs">
                <span class="text-gray-400 text-xs">–</span>
                <input type="date" name="datum_do" value="{{ request('datum_do') }}"
                    title="Datum do" class="rounded border border-gray-300 px-2 py-1 text-xs">
            </div>

            <label class="text-xs text-gray-500 flex items-center gap-1">
                <input type="checkbox" name="vse" value="1" {{ request('vse') ? 'checked' : '' }} class="rounded">
                I minulé
            </label>

            <label class="text-xs text-gray-500 flex items-center gap-1">
                <input type="checkbox" name="moje_rezervovane" value="1" {{ request('moje_rezervovane') ? 'checked' : '' }} class="rounded">
                Moje rezervované
            </label>

            @if($u?->maAdresu())
                <label class="text-xs text-gray-500 flex items-center gap-1" title="Pouze akce do X km od mého sídla">
                    Do
                    <input type="number" name="radius" value="{{ request('radius') }}" min="1" max="1000" placeholder="—"
                        class="w-16 rounded border border-gray-300 px-1 py-0.5 text-xs">
                    km
                </label>
            @endif

            <button type="submit" class="rounded bg-primary px-3 py-1 text-xs font-medium text-white hover:bg-primary-dark transition">
                Filtrovat
            </button>
            @if($maFiltr || request()->boolean('moje_rezervovane'))
                <a href="{{ url('/akce?_clear=1') }}" class="text-xs text-gray-500 hover:text-primary">Zrušit</a>
            @endif
        </div>
    </form>

    {{-- Souhrn + odkaz na mapu --}}
    <div class="flex items-center justify-between mb-3">
        <p class="text-sm text-gray-600">
            @if($maFiltr)
                Nalezeno <strong>{{ $akce->total() }}</strong> akcí
            @else
                Celkem <strong>{{ $akce->total() }}</strong> akcí
            @endif
        </p>
        <a href="{{ url('/mapa') }}" class="text-sm text-primary hover:text-primary-dark">
            🗺️ Zobrazit akce na mapě
        </a>
    </div>

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
                    $aktivniRezervace = $a->rezervace ?? collect();
                    $mojeRezervace = $aktivniRezervace->firstWhere('uzivatel_id', Auth::id());
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition"
                     x-data="{
                        open: false,
                        palec: @js($mujPalec),
                        poznamka: @js($mojePoznamka),
                        rezervovano: @js((bool) $mojeRezervace),
                        async setPalec(novy) {
                            if (this.rezervovano) {
                                alert('Akce je rezervovaná — palec uzamčen na nahoru. Pro změnu nejprve zrušte rezervaci.');
                                return;
                            }
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
                        },
                        async toggleRezervace() {
                            const url = '{{ route('akce.rezervovat', $a) }}';
                            const fd = new FormData();
                            fd.append('_token', @js(csrf_token()));
                            if (this.rezervovano) {
                                fd.append('_method', 'DELETE');
                                await fetch(url, { method: 'POST', body: fd });
                                this.rezervovano = false;
                            } else {
                                await fetch(url, { method: 'POST', body: fd });
                                this.rezervovano = true;
                                this.palec = 'nahoru';
                            }
                        }
                     }">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <button type="button" @click="open = !open" class="font-medium text-gray-800 hover:text-primary text-left flex items-center gap-1" title="Zobrazit / skrýt detaily">
                                    <span class="text-gray-400 text-xs transition-transform inline-block" :class="open ? 'rotate-180' : ''">▼</span>
                                    <span>{{ $a->nazev }}</span>
                                </button>
                                @if($a->stav === 'zrusena')
                                    <span class="rounded-full bg-red-100 text-red-700 px-2 py-0.5 text-xs font-medium">zrušena</span>
                                @elseif($a->stav === 'navrh')
                                    <span class="rounded-full bg-yellow-100 text-yellow-700 px-2 py-0.5 text-xs font-medium">návrh</span>
                                @endif
                                @if($a->velikost_stav === 'ano')
                                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium" style="color: var(--c-primary);" title="AI klasifikovala akci jako velkou (skóre {{ $a->velikost_skore }})">velká akce</span>
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
                                {{-- Vzdálenost + směr od sídla uživatele --}}
                                @if($userLat && $userLng)
                                    @if($a->gps_lat && $a->gps_lng)
                                        @php
                                            $km = \App\Support\Vzdalenost::km($userLat, $userLng, $a->gps_lat, $a->gps_lng);
                                            $bearing = \App\Support\Vzdalenost::smerStupne($userLat, $userLng, $a->gps_lat, $a->gps_lng);
                                            $sipka = \App\Support\Vzdalenost::smerSipka($bearing);
                                        @endphp
                                        · <span class="font-medium text-gray-700" title="Směr od mého sídla">{{ $sipka }} {{ \App\Support\Vzdalenost::formatuj($km) }}</span>
                                    @else
                                        · <span class="text-gray-400" title="Akce nemá GPS souřadnice">?</span>
                                    @endif
                                @endif
                            </p>
                            @if($a->organizator)
                                <p class="text-xs text-gray-400 mt-0.5">Organizátor: {{ $a->organizator }}</p>
                            @endif
                            {{-- Aktivní rezervace — viditelné všem uživatelům --}}
                            @if($aktivniRezervace->isNotEmpty())
                                <p class="text-xs text-green-700 mt-1">
                                    ✅ Rezervováno:
                                    @foreach($aktivniRezervace as $rez)
                                        <span class="font-medium">{{ $rez->uzivatel?->jmeno }} {{ $rez->uzivatel?->prijmeni }}</span>{{ !$loop->last ? ',' : '' }}
                                    @endforeach
                                </p>
                            @endif
                            {{-- Osobní poznámka — viditelná v hlavním výpise --}}
                            <p x-show="poznamka" x-cloak class="text-xs text-purple-700 mt-1 italic" x-text="'📝 ' + poznamka"></p>
                        </div>

                        <div class="flex flex-col items-end gap-1 shrink-0">
                            {{-- Palec hodnocení (inline styly — Tailwind nekompiluje dynamické :class) --}}
                            <div class="flex gap-1">
                                <button type="button" @click="setPalec('nahoru')" :disabled="rezervovano"
                                    :style="palec === 'nahoru' ? 'background-color:#22c55e;color:#fff;border-color:#16a34a;' : ''"
                                    :title="rezervovano ? 'Uzamčeno — akce je rezervovaná' : 'Líbí se mi (nahoru v seznamu)'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center border border-gray-200 bg-gray-100 transition disabled:cursor-not-allowed disabled:opacity-80">👍</button>
                                <button type="button" @click="setPalec('stred')" :disabled="rezervovano"
                                    :style="palec === 'stred' ? 'background-color:#f97316;color:#fff;border-color:#ea580c;' : ''"
                                    :title="rezervovano ? 'Uzamčeno — akce je rezervovaná' : 'Možná (uprostřed seznamu)'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center border border-gray-200 bg-gray-100 transition disabled:cursor-not-allowed disabled:opacity-50">👉</button>
                                <button type="button" @click="setPalec('dolu')" :disabled="rezervovano"
                                    :style="palec === 'dolu' ? 'background-color:#ef4444;color:#fff;border-color:#dc2626;' : ''"
                                    :title="rezervovano ? 'Uzamčeno — akce je rezervovaná' : 'Nezajímá mě (dole)'"
                                    class="w-7 h-7 rounded-full text-sm flex items-center justify-center border border-gray-200 bg-gray-100 transition disabled:cursor-not-allowed disabled:opacity-50">👎</button>
                            </div>
                            {{-- Rezervovat / Zrušit rezervaci --}}
                            <button type="button" @click="toggleRezervace"
                                :class="rezervovano ? 'bg-red-50 text-red-700 border-red-300 hover:bg-red-100' : 'bg-white text-primary border-primary hover:bg-primary/10'"
                                class="rounded-lg border px-3 py-1 text-xs font-medium transition"
                                title="Vaši rezervaci uvidí i ostatní — berte ji jako ZÁVAZNOU, nebo pouze krátkodobou ve stylu ŘEŠÍM TO.">
                                <span x-text="rezervovano ? '✕ Zrušit rezervaci' : 'Rezervovat'"></span>
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
