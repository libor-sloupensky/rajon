<x-layouts.app title="Správa akcí — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Správa akcí ({{ $akce->total() }})</h1>
        <a href="{{ route('admin.akce.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
            + Nová akce
        </a>
    </div>

    <form method="GET" class="mb-4 flex gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Stav</label>
            <select name="stav" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">Všechny</option>
                @foreach(['navrh', 'overena', 'zrusena'] as $s)
                    <option value="{{ $s }}" {{ request('stav') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white">Filtrovat</button>
    </form>

    @if($akce->isEmpty())
        <p class="text-gray-500">Žádné akce. Spusť scraping nebo přidej ručně.</p>
    @else
        <div class="space-y-2">
            @foreach($akce as $a)
                <div class="rounded-lg border border-gray-200 bg-white p-4 flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <a href="{{ route('admin.akce.edit', $a) }}" class="font-medium text-gray-800 hover:text-primary">{{ $a->nazev }}</a>
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                {{ $a->stav === 'overena' ? 'bg-green-100 text-green-700' :
                                   ($a->stav === 'zrusena' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ $a->stav }}
                            </span>
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
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $a->datum_od?->format('j. n. Y') }}
                            @if($a->datum_do && $a->datum_do->ne($a->datum_od))
                                — {{ $a->datum_do->format('j. n. Y') }}
                            @endif
                            · {{ $a->misto }} · {{ $a->kraj }}
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0 ml-4">
                        <a href="{{ route('admin.akce.edit', $a) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">Upravit</a>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $akce->withQueryString()->links() }}</div>
    @endif
</x-layouts.app>
