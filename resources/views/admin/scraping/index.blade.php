<x-layouts.app title="Scraping — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Scraping zdrojů</h1>
        <div class="flex gap-2 items-center">
            <button type="button" onclick="location.reload()" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50" title="Obnovit stav">↻ Obnovit</button>
            <a href="{{ route('admin.scraping.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                + Přidat zdroj
            </a>
        </div>
    </div>

    {{-- Stav scrapingu — souhrn nahoře --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
        <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">Stav scrapingu</h2>

        @if($bezi->isNotEmpty())
            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 mb-3">
                <p class="text-sm font-medium text-blue-800">
                    🔄 Běží scraping
                    <span class="text-xs font-normal text-blue-600 ml-2">
                        (stránka se obnoví za <span id="rj-refresh-sec">30</span> s)
                    </span>
                </p>
                <ul class="mt-2 text-xs text-blue-700 space-y-0.5">
                    @foreach($bezi as $log)
                        <li>
                            <strong>{{ $log->zdroj->nazev }}</strong> — začalo {{ $log->zacatek?->diffForHumans() }},
                            zatím {{ $log->pocet_novych ?? 0 }} nových / {{ $log->pocet_aktualizovanych ?? 0 }} upd.
                        </li>
                    @endforeach
                </ul>
            </div>
            <script>
                // Auto-refresh za 30 s, pokud něco běží
                (function () {
                    var s = 30;
                    var el = document.getElementById('rj-refresh-sec');
                    var timer = setInterval(function () {
                        s--;
                        if (el) el.textContent = s;
                        if (s <= 0) { clearInterval(timer); location.reload(); }
                    }, 1000);
                })();
            </script>
        @else
            <p class="text-sm text-green-700">✅ Žádný scraping aktuálně neběží — vše je dokončeno.</p>
        @endif

        {{-- Per zdroj: kdy bude další běh --}}
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs">
            @foreach($posledniDokonceni as $z)
                @php
                    $kdy = $z['kdy'];
                    $hodPred = $kdy ? max(0, abs(now()->diffInHours($kdy, false))) : null;
                    $dalsi = $kdy ? $hodPred >= $z['frekvence_hodin'] : true;
                @endphp
                <div class="rounded bg-gray-50 p-2 border border-gray-100">
                    <div class="font-medium text-gray-800">{{ $z['nazev'] }}</div>
                    <div class="text-gray-500">
                        @if($kdy)
                            Naposled: <span title="{{ $kdy->format('j. n. Y H:i') }}">{{ $kdy->diffForHumans() }}</span>
                        @else
                            Nikdy
                        @endif
                    </div>
                    <div class="{{ $dalsi ? 'text-orange-600' : 'text-gray-400' }}">
                        @if($dalsi)
                            Další cron běh: připraveno
                        @else
                            Další za {{ $z['frekvence_hodin'] - $hodPred }} h
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Cost widget — náklady na AI extrakce --}}
    @php
        $f = fn ($v, $d = 2) => number_format($v, $d, ',', ' ');
    @endphp
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4">
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Náklady na AI extrakce</h2>
            <span class="text-xs text-gray-500">kurz {{ $f($kurz, 2) }} Kč/USD · model Haiku 4.5 ($1/$5/Mtok)</span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach (['dnes' => 'Dnes', 'tyden' => 'Posledních 7 dní', 'mesic' => 'Posledních 30 dní', 'celkem' => 'Celkem'] as $klic => $label)
                @php $s = $statsZakladni[$klic]; @endphp
                <div class="rounded bg-gray-50 p-3">
                    <div class="text-xs font-medium text-gray-500 uppercase">{{ $label }}</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">${{ $f($s['cena_usd'], 4) }}</div>
                    <div class="text-sm text-gray-600">{{ $f($s['cena_usd'] * $kurz, 2) }} Kč</div>
                    <div class="mt-1 text-xs text-gray-400">{{ $f($s['pocet'], 0) }} volání · {{ $f($s['tokens'] / 1000, 1) }}k tokenů</div>
                </div>
            @endforeach
        </div>

        @if (count($statsPerUzivatel) > 0)
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-gray-600 hover:text-primary">Per uživatel (30 dní)</summary>
                <table class="mt-2 w-full text-xs">
                    <thead class="text-gray-500 uppercase">
                        <tr>
                            <th class="text-left py-1">Uživatel</th>
                            <th class="text-right py-1">Volání</th>
                            <th class="text-right py-1">USD</th>
                            <th class="text-right py-1">Kč</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($statsPerUzivatel as $u)
                            <tr>
                                <td class="py-1">{{ $u['jmeno'] }}</td>
                                <td class="text-right py-1">{{ $f($u['pocet'], 0) }}</td>
                                <td class="text-right py-1">${{ $f($u['cena_usd'], 4) }}</td>
                                <td class="text-right py-1">{{ $f($u['cena_usd'] * $kurz, 2) }} Kč</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        @endif
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
                                <input type="hidden" name="limit" value="50">
                                <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-dark transition"
                                        onclick="return confirm('Spustit scraping (50 URL)? Může trvat ~30-60 sekund.')"
                                        title="Synchronní limit, prevence Gateway Timeout. Plný scraping bude přes cron.">
                                    Spustit (50)
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
