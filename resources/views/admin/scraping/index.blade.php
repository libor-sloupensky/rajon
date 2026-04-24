<x-layouts.app title="Scraping — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Scraping zdrojů</h1>
        <a href="{{ route('admin.scraping.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
            + Přidat zdroj
        </a>
    </div>

    {{-- Zdroje --}}
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Zdroje ({{ $zdroje->count() }})</h2>
        <div class="space-y-2">
            @foreach($zdroje as $zdroj)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-medium text-gray-800">{{ $zdroj->nazev }}</h3>
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $zdroj->stav === 'aktivni' ? 'bg-green-100 text-green-700' :
                                       ($zdroj->stav === 'chyba' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $zdroj->stav }}
                                </span>
                                @if($zdroj->cms_typ)
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $zdroj->cms_typ }}</span>
                                @endif
                            </div>
                            <a href="{{ $zdroj->url }}" target="_blank" class="text-xs text-gray-500 hover:text-primary">{{ $zdroj->url }}</a>
                            <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">
                                <span>Akcí: <strong>{{ $zdroj->akce_count }}</strong></span>
                                <span>Poslední scraping: {{ $zdroj->posledni_scraping?->diffForHumans() ?? 'nikdy' }}</span>
                                <span>Frekvence: {{ $zdroj->frekvence_hodin }} h</span>
                            </div>
                            @if($zdroj->poznamka)
                                <p class="mt-2 text-xs text-gray-600">{{ $zdroj->poznamka }}</p>
                            @endif
                        </div>
                        <div class="flex gap-2 shrink-0 ml-4">
                            <form method="POST" action="{{ route('admin.scraping.spustit', $zdroj) }}">
                                @csrf
                                <input type="hidden" name="limit" value="10">
                                <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-dark transition"
                                        onclick="return confirm('Spustit scraping (test, limit 10 akcí)?')">
                                    Test (10)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.scraping.spustit', $zdroj) }}">
                                @csrf
                                <input type="hidden" name="limit" value="0">
                                <button type="submit" class="rounded-lg border border-primary px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/10 transition"
                                        onclick="return confirm('Spustit plný scraping? Může trvat dlouho.')">
                                    Plný scraping
                                </button>
                            </form>
                            <a href="{{ route('admin.scraping.edit', $zdroj) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition">
                                Upravit
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Poslední běhy --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-700 mb-3">Poslední běhy ({{ $posledniLogy->count() }})</h2>
        @if($posledniLogy->isEmpty())
            <p class="text-sm text-gray-500">Zatím žádné běhy.</p>
        @else
            <div class="space-y-2">
                @foreach($posledniLogy as $log)
                    <a href="{{ route('admin.scraping.log', $log) }}" class="block rounded-lg border border-gray-200 bg-white p-3 hover:border-primary transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-gray-800">{{ $log->zdroj->nazev }}</span>
                                <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $log->stav === 'uspech' ? 'bg-green-100 text-green-700' :
                                       ($log->stav === 'chyba' ? 'bg-red-100 text-red-700' :
                                       ($log->stav === 'castecne' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')) }}">
                                    {{ $log->stav }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                <span>{{ $log->zacatek?->diffForHumans() }}</span>
                                <span class="ml-3">
                                    {{ $log->pocet_novych }} nových /
                                    {{ $log->pocet_aktualizovanych }} upd. /
                                    {{ $log->pocet_preskocenych }} skip /
                                    {{ $log->pocet_chyb }} chyb
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
