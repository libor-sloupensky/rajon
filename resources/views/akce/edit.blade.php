<x-layouts.app title="Upravit akci — Rajón">
    <div class="max-w-3xl">
        <a href="{{ route('akce.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět na katalog</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $akce->nazev }}</h1>
        <p class="text-sm text-gray-500 mb-6">
            Velikost: <strong>{{ $akce->velikost_stav }}</strong> (skóre {{ $akce->velikost_skore }})
            @if($akce->zdroj)
                · Původní zdroj: <strong>{{ $akce->zdroj->nazev }}</strong>
            @endif
        </p>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Návrh ročníkového propojení --}}
        @if(!empty($akce->navrh_propojeni))
            <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-4">
                <h3 class="font-medium text-blue-900 mb-2">🔗 AI navrhuje propojení s předchozími ročníky</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    @foreach($akce->navrh_propojeni as $navrh)
                        <li>
                            <a href="{{ route('akce.edit', $navrh['akce_id']) }}" class="underline">{{ $navrh['nazev'] }}</a>
                            — {{ $navrh['datum_od'] }} · {{ $navrh['misto'] }}
                            <span class="text-xs text-blue-600">(podobnost {{ $navrh['similarity'] }}%)</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Konflikty --}}
        @if(!empty($akce->konflikty))
            <div class="mb-4 rounded-lg bg-orange-50 border border-orange-200 p-4">
                <h3 class="font-medium text-orange-900 mb-2">⚠️ Konflikty mezi zdroji ({{ count($akce->konflikty) }})</h3>
                <div class="space-y-2 text-sm">
                    @foreach($akce->konflikty as $k)
                        <div class="rounded bg-white p-2 border border-orange-200">
                            <strong>{{ $k['pole'] }}</strong>:
                            <span class="text-gray-700">{{ $k['puvodni']['hodnota'] ?? '?' }}</span> (ze zdroje {{ $k['puvodni']['zdroj'] }}, trust {{ $k['puvodni']['trust'] }})
                            vs.
                            <span class="text-gray-700">{{ $k['novy']['hodnota'] ?? '?' }}</span> (ze zdroje {{ $k['novy']['zdroj'] }}, trust {{ $k['novy']['trust'] }})
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('akce.update', $akce) }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-4">
            @csrf
            @method('PUT')

            @php
                $polePopisky = [
                    'nazev' => 'Název',
                    'typ' => 'Typ',
                    'datum_od' => 'Datum od',
                    'datum_do' => 'Datum do',
                    'misto' => 'Místo',
                    'adresa' => 'Adresa',
                    'gps_lat' => 'GPS lat',
                    'gps_lng' => 'GPS lng',
                    'okres' => 'Okres',
                    'kraj' => 'Kraj',
                    'organizator' => 'Organizátor',
                    'kontakt_email' => 'E-mail',
                    'kontakt_telefon' => 'Telefon',
                    'web_url' => 'Web',
                    'najem' => 'Nájem (Kč)',
                    'obrat' => 'Obrat (Kč)',
                    'popis' => 'Popis',
                    'stav' => 'Stav',
                    'poznamka' => 'Poznámka',
                ];
                $typy = [
                    'pout' => 'Pouť',
                    'food_festival' => 'Food festival',
                    'slavnosti' => 'Slavnosti',
                    'mestske_slavnosti' => 'Městské slavnosti',
                    'obrani' => 'Obraní (vinobraní/dýňobraní/...)',
                    'farmarske_trhy' => 'Farmářské trhy',
                    'vanocni_trhy' => 'Vánoční trhy',
                    'velikonocni_trhy' => 'Velikonoční trhy',
                    'jarmark' => 'Jarmark',
                    'festival' => 'Festival',
                    'sportovni_akce' => 'Sportovní akce',
                    'koncert' => 'Koncert',
                    'divadlo' => 'Divadlo',
                    'vystava' => 'Výstava',
                    'workshop' => 'Workshop',
                    'jiny' => 'Jiný',
                ];
                $stavy = ['navrh' => 'Návrh', 'overena' => 'Ověřená', 'zrusena' => 'Zrušená'];
            @endphp

            @foreach($polePopisky as $pole => $popisek)
                @php
                    $uzamceno = $akce->jePoleUzamceno($pole);
                    $zdrojPole = $akce->pole_zdroje[$pole] ?? null;
                @endphp
                <div class="grid grid-cols-[1fr_auto] gap-2 items-end">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <label class="text-sm font-medium text-gray-700">{{ $popisek }}</label>
                            @if($uzamceno)
                                <span class="text-xs text-orange-600" title="Pole je zamčené — scraping ho nepřepíše">🔒</span>
                            @endif
                            @if($zdrojPole)
                                <span class="text-xs text-gray-400">ze zdroje: <strong>{{ $zdrojPole }}</strong></span>
                            @endif
                        </div>

                        @if($pole === 'typ')
                            <select name="{{ $pole }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @foreach($typy as $v => $l)
                                    <option value="{{ $v }}" {{ $akce->$pole === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        @elseif($pole === 'stav')
                            <select name="{{ $pole }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @foreach($stavy as $v => $l)
                                    <option value="{{ $v }}" {{ $akce->$pole === $v ? 'selected' : '' }}>{{ $l }}</option>
                                @endforeach
                            </select>
                        @elseif($pole === 'popis' || $pole === 'poznamka')
                            <textarea name="{{ $pole }}" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old($pole, $akce->$pole) }}</textarea>
                        @elseif(in_array($pole, ['datum_od', 'datum_do']))
                            <input type="date" name="{{ $pole }}" value="{{ old($pole, $akce->$pole?->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @elseif(in_array($pole, ['gps_lat', 'gps_lng']))
                            <input type="number" step="0.0000001" name="{{ $pole }}" value="{{ old($pole, $akce->$pole) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @elseif(in_array($pole, ['najem', 'obrat']))
                            <input type="number" name="{{ $pole }}" value="{{ old($pole, $akce->$pole) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @elseif($pole === 'kontakt_email')
                            <input type="email" name="{{ $pole }}" value="{{ old($pole, $akce->$pole) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @elseif($pole === 'web_url')
                            <input type="url" name="{{ $pole }}" value="{{ old($pole, $akce->$pole) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @else
                            <input type="text" name="{{ $pole }}" value="{{ old($pole, $akce->$pole) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @endif
                        @error($pole) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    @if($uzamceno)
                        <button type="submit" form="odemknout-{{ $pole }}" class="rounded-lg border border-gray-300 px-2 py-1 text-xs text-gray-600 hover:bg-gray-50 self-end mb-1" title="Odemknout pole — scraping ho zase bude aktualizovat">Odemknout</button>
                    @endif
                </div>
            @endforeach

            {{-- Admin komentář --}}
            <div class="rounded-lg border border-purple-200 bg-purple-50 p-3">
                <label class="block text-sm font-medium text-purple-900 mb-1">
                    📝 Admin komentář <span class="text-xs text-purple-600">(AI nezasahuje, jen ruční úpravy / XLS import)</span>
                </label>
                <textarea name="admin_komentar" rows="3" class="w-full rounded-lg border border-purple-300 bg-white px-3 py-2 text-sm">{{ old('admin_komentar', $akce->admin_komentar) }}</textarea>
            </div>

            {{-- Velikost info (read-only display) --}}
            @if($akce->velikost_info)
                <div class="rounded bg-gray-50 p-3 border border-gray-200">
                    <div class="text-xs font-medium text-gray-700 mb-1">AI info o velikosti</div>
                    <pre class="text-xs text-gray-600 whitespace-pre-wrap">{{ $akce->velikost_info }}</pre>
                </div>
            @endif

            {{-- Velikost signaly --}}
            @if($akce->velikost_signaly)
                <div class="rounded bg-gray-50 p-3 border border-gray-200">
                    <div class="text-xs font-medium text-gray-700 mb-1">Velikostní signály</div>
                    <div class="text-xs text-gray-600 space-y-0.5">
                        @foreach($akce->velikost_signaly as $k => $v)
                            @if($v !== null)
                                <div>{{ $k }}: <strong>{{ $v }}</strong></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark">
                    Uložit změny (auto-lock upravených polí)
                </button>
                @if(Auth::user()?->jeAdmin())
                    <button type="submit" form="form-smazat" class="rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-medium text-red-600 hover:bg-red-50">
                        Smazat
                    </button>
                @endif
            </div>
        </form>

        @if(Auth::user()?->jeAdmin())
            <form id="form-smazat" method="POST" action="{{ route('akce.destroy', $akce) }}" onsubmit="return confirm('Opravdu smazat akci {{ $akce->nazev }}?')" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif

        {{-- Pomocné formuláře pro odemknutí polí (musí být mimo hlavní form) --}}
        @foreach($polePopisky as $pole => $popisek)
            @if($akce->jePoleUzamceno($pole))
                <form id="odemknout-{{ $pole }}" method="POST" action="{{ route('akce.odemknout-pole', $akce) }}" class="hidden">
                    @csrf
                    <input type="hidden" name="pole" value="{{ $pole }}">
                </form>
            @endif
        @endforeach

        {{-- Zdroje (akce_zdroje) --}}
        @if($akce->akceZdroje->isNotEmpty())
            <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="font-medium text-gray-700 mb-2">Zdroje této akce ({{ $akce->akceZdroje->count() }})</h3>
                <div class="space-y-2 text-sm">
                    @foreach($akce->akceZdroje as $az)
                        <div class="flex items-center justify-between">
                            <a href="{{ $az->url }}" target="_blank" class="text-primary hover:underline">{{ $az->zdroj?->nazev ?? '?' }}</a>
                            <span class="text-xs text-gray-500">{{ $az->posledni_ziskani?->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Merge log --}}
        @if(!empty($akce->merge_log))
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="font-medium text-gray-700 mb-2">Historie merge ({{ count($akce->merge_log) }})</h3>
                <div class="space-y-1 text-xs text-gray-600 max-h-60 overflow-y-auto">
                    @foreach(array_reverse($akce->merge_log) as $entry)
                        <div class="border-l-2 border-gray-200 pl-2">
                            <div class="font-medium">{{ $entry['datum'] ?? '' }} · ze zdroje {{ $entry['zdroj'] ?? '?' }}</div>
                            @if(!empty($entry['zmeny']))
                                <div class="text-gray-500">{{ implode(', ', array_keys($entry['zmeny'])) }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-layouts.app>
