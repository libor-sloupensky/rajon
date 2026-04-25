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
    <form method="GET" class="mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
        <div class="col-span-2 lg:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Hledat (název, místo, organizátor)</label>
            <input type="text" name="hledat" value="{{ request('hledat') }}" placeholder="Hledat..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Typ</label>
            <select name="typ" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Všechny</option>
                @foreach(['pout' => 'Pouť', 'food_festival' => 'Food festival', 'slavnosti' => 'Slavnosti', 'mestske_slavnosti' => 'Městské slavnosti', 'obrani' => 'Obraní (vino/dýňo/...)', 'farmarske_trhy' => 'Farmářské trhy', 'vanocni_trhy' => 'Vánoční trhy', 'velikonocni_trhy' => 'Velikonoční trhy', 'jarmark' => 'Jarmark', 'festival' => 'Festival', 'sportovni_akce' => 'Sportovní akce', 'koncert' => 'Koncert', 'vystava' => 'Výstava', 'workshop' => 'Workshop', 'jiny' => 'Jiný'] as $val => $label)
                    <option value="{{ $val }}" {{ request('typ') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kraj</label>
            <input type="text" name="kraj" value="{{ request('kraj') }}" placeholder="Kraj..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Datum od</label>
            <input type="date" name="datum_od" value="{{ request('datum_od') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Datum do</label>
            <input type="date" name="datum_do" value="{{ request('datum_do') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        @if($jeAdmin)
            <div>
                <label class="block text-xs text-gray-500 mb-1">Stav</label>
                <select name="stav" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Všechny (kromě zrušených)</option>
                    @foreach(['navrh' => 'Návrh', 'overena' => 'Ověřená', 'zrusena' => 'Zrušená'] as $v => $l)
                        <option value="{{ $v }}" {{ request('stav') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex items-center gap-2">
            <label class="text-xs text-gray-500 flex items-center gap-1">
                <input type="checkbox" name="vse" value="1" {{ request('vse') ? 'checked' : '' }} class="rounded">
                I minulé
            </label>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                Filtrovat
            </button>
            @if(request()->hasAny(['hledat', 'typ', 'kraj', 'mesic', 'rok', 'datum_od', 'datum_do', 'stav', 'vse']))
                <a href="{{ url('/akce') }}" class="text-sm text-gray-500 hover:text-primary self-center">Zrušit</a>
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
                                @if(!empty($a->pole_manualni))
                                    <span class="text-xs text-gray-500" title="Manuálně upravená pole: {{ implode(', ', array_keys($a->pole_manualni)) }}">🔒 {{ count($a->pole_manualni) }}</span>
                                @endif
                                @if(!empty($a->konflikty))
                                    <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700" title="Konflikty mezi zdroji">⚠️ {{ count($a->konflikty) }}</span>
                                @endif
                                @if(!empty($a->navrh_propojeni))
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700" title="AI navrhlo propojení s jinou akcí">🔗 {{ count($a->navrh_propojeni) }}</span>
                                @endif
                                @if($a->typ && $a->typ !== 'jiny')
                                    <span class="rounded-full bg-gray-100 text-gray-600 px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $a->typ) }}</span>
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
