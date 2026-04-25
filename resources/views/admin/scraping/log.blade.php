<x-layouts.app title="Scraping log — Rajón">
    <div class="max-w-3xl">
        <a href="{{ route('admin.scraping.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Scraping log #{{ $log->id }}</h1>
        <p class="text-sm text-gray-500 mb-6">Zdroj: <strong>{{ $log->zdroj->nazev }}</strong></p>

        <div class="rounded-lg border border-gray-200 bg-white p-4 space-y-4">
            <div class="flex items-center gap-2">
                <span class="font-medium">Stav:</span>
                <span class="rounded-full px-3 py-1 text-sm font-medium
                    {{ $log->stav === 'uspech' ? 'bg-green-100 text-green-700' :
                       ($log->stav === 'chyba' ? 'bg-red-100 text-red-700' :
                       ($log->stav === 'castecne' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')) }}">
                    {{ $log->stav }}
                </span>
            </div>

            @php
                $limit = (int) ($log->limit_pouzity ?? 0);
                $zpracovano = $log->pocet_zpracovanych;
                $rezim = $limit > 0 ? "Test (limit {$limit})" : 'Plný scraping';
            @endphp

            <div class="rounded bg-gray-50 border border-gray-200 p-3 text-sm">
                <div class="flex flex-wrap items-baseline justify-between gap-3">
                    <div>
                        <span class="font-medium text-gray-700">Režim:</span>
                        <strong>{{ $rezim }}</strong>
                    </div>
                    <div class="text-gray-600">
                        <span>V sitemapu / listingu nalezeno <strong>{{ number_format($log->pocet_nalezenych, 0, ',', ' ') }}</strong> URL</span>
                        <span class="mx-2 text-gray-400">·</span>
                        <span>zpracováno <strong>{{ $zpracovano }}</strong></span>
                        @if($limit > 0 && $log->pocet_nalezenych > $limit)
                            <span class="ml-2 text-xs text-gray-500">(zbývá {{ $log->pocet_nalezenych - $zpracovano }} pro plný běh)</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-3 text-center">
                <div class="rounded bg-green-50 p-3">
                    <div class="text-2xl font-bold text-green-700">{{ $log->pocet_novych }}</div>
                    <div class="text-xs text-gray-500">Nových</div>
                </div>
                <div class="rounded bg-blue-50 p-3">
                    <div class="text-2xl font-bold text-blue-700">{{ $log->pocet_aktualizovanych }}</div>
                    <div class="text-xs text-gray-500">Aktualizovaných</div>
                </div>
                <div class="rounded bg-yellow-50 p-3">
                    <div class="text-2xl font-bold text-yellow-700">{{ $log->pocet_preskocenych }}</div>
                    <div class="text-xs text-gray-500">Přeskočených</div>
                </div>
                <div class="rounded bg-red-50 p-3">
                    <div class="text-2xl font-bold text-red-700">{{ $log->pocet_chyb }}</div>
                    <div class="text-xs text-gray-500">Chyb</div>
                </div>
            </div>

            <div class="text-sm text-gray-600">
                <p>Začátek: {{ $log->zacatek?->format('j. n. Y H:i:s') }}</p>
                <p>Konec: {{ $log->konec?->format('j. n. Y H:i:s') }}</p>
                @if($log->zacatek && $log->konec)
                    <p>Trvání: {{ $log->zacatek->diffInSeconds($log->konec) }} s</p>
                @endif
            </div>

            @if($log->statistiky)
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="font-medium text-gray-700 mb-2">Statistiky</h3>
                    @if(!empty($log->statistiky['podle_kraje']))
                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Podle kraje</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($log->statistiky['podle_kraje'] as $kraj => $pocet)
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $kraj }}: {{ $pocet }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!empty($log->statistiky['podle_velikosti']))
                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Podle velikosti</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($log->statistiky['podle_velikosti'] as $stav => $pocet)
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $stav }}: {{ $pocet }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!empty($log->statistiky['podle_typu']))
                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Podle typu</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($log->statistiky['podle_typu'] as $typ => $pocet)
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $typ }}: {{ $pocet }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!empty($log->statistiky['preskoceno_z_duvodu']))
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Důvody přeskočení</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($log->statistiky['preskoceno_z_duvodu'] as $duvod => $pocet)
                                    <span class="rounded bg-yellow-100 text-yellow-800 px-2 py-0.5 text-xs">{{ $duvod }}: {{ $pocet }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($log->chyby_detail)
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="font-medium text-gray-700 mb-2">Chyby</h3>
                    <pre class="text-xs bg-red-50 p-3 rounded overflow-x-auto">{{ $log->chyby_detail }}</pre>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
