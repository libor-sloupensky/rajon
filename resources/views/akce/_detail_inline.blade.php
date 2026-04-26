@php
    $polePopis = [
        'datum_od' => ['Datum od', 'date'],
        'datum_do' => ['Datum do', 'date'],
        'typ' => ['Typ', 'select'],
        'misto' => ['Místo', 'text'],
        'adresa' => ['Adresa', 'text'],
        'okres' => ['Okres', 'text'],
        'kraj' => ['Kraj', 'select-kraj'],
        'organizator' => ['Organizátor', 'text'],
        'kontakt_email' => ['E-mail', 'email'],
        'kontakt_telefon' => ['Telefon', 'text'],
        'web_url' => ['Web', 'url'],
        'vstupne' => ['Vstupné', 'text'],
        'popis' => ['Popis', 'textarea'],
        'najem' => ['Nájem (Kč)', 'number'],
        'obrat' => ['Obrat (Kč)', 'number'],
        'stav' => ['Stav', 'select-stav'],
    ];
    $typyOpts = [
        'pout' => 'Pouť',
        'food_festival' => 'Food festival',
        'slavnosti' => 'Slavnosti a městské akce',
        'obrani' => 'Obraní',
        'trhy_jarmarky' => 'Trhy a jarmarky',
        'festival' => 'Festival',
        'sportovni_akce' => 'Sportovní akce',
        'koncert' => 'Koncert',
        'divadlo' => 'Divadlo',
        'vystava' => 'Výstava',
        'workshop' => 'Workshop',
        'prednaska' => 'Přednáška',
        'jiny' => 'Jiný',
    ];
    $krajeOpts = [
        'Praha', 'Středočeský kraj', 'Jihočeský kraj', 'Plzeňský kraj',
        'Karlovarský kraj', 'Ústecký kraj', 'Liberecký kraj',
        'Královéhradecký kraj', 'Pardubický kraj', 'Kraj Vysočina',
        'Jihomoravský kraj', 'Olomoucký kraj', 'Zlínský kraj',
        'Moravskoslezský kraj',
    ];
    $stavyOpts = ['navrh' => 'Návrh', 'overena' => 'Ověřená', 'zrusena' => 'Zrušená'];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-sm"
     x-data="{
        editPole: null,
        startEdit(pole, val) { this.editPole = pole; this.tempVal = val ?? ''; },
        async ulozPole(pole) {
            const fd = new FormData();
            fd.append('_token', @js(csrf_token()));
            fd.append('_method', 'PATCH');
            fd.append('pole', pole);
            fd.append('hodnota', this.tempVal ?? '');
            const r = await fetch('{{ route('akce.inline', $a) }}', { method: 'POST', body: fd });
            if (r.ok) {
                document.getElementById('val-' + pole + '-{{ $a->id }}').textContent = this.tempVal || '—';
                this.editPole = null;
            } else {
                alert('Chyba při ukládání');
            }
        },
     }">
    @foreach($polePopis as $pole => [$label, $typInput])
        @php
            $hodnota = $a->$pole;
            $hodnotaZobrazit = $hodnota;
            if ($pole === 'datum_od' || $pole === 'datum_do') {
                $hodnotaZobrazit = $hodnota?->format('j. n. Y') ?? '—';
                $hodnota = $hodnota?->format('Y-m-d');
            } elseif ($pole === 'typ') {
                $hodnotaZobrazit = $typyOpts[$hodnota] ?? $hodnota;
            } elseif ($pole === 'stav') {
                $hodnotaZobrazit = $stavyOpts[$hodnota] ?? $hodnota;
            }
            $hodnotaZobrazit = $hodnotaZobrazit ?: '—';
            $isLocked = isset(($a->pole_manualni ?? [])[$pole]);
        @endphp
        <div class="py-0.5">
            <span class="text-xs text-gray-500">{{ $label }}:</span>
            <div x-show="editPole !== '{{ $pole }}'" class="inline-flex items-center gap-1">
                <span id="val-{{ $pole }}-{{ $a->id }}" class="text-gray-700">{{ $hodnotaZobrazit }}</span>
                @if($isLocked)<span class="text-xs text-orange-500" title="manuálně upraveno">🔒</span>@endif
                <button type="button" @click="startEdit('{{ $pole }}', @js((string) $hodnota))"
                    class="text-gray-400 hover:text-primary text-xs ml-1" title="Upravit">✏️</button>
            </div>
            <div x-show="editPole === '{{ $pole }}'" x-cloak class="inline-flex items-center gap-1">
                @if($typInput === 'select')
                    <select x-model="tempVal" class="rounded border border-gray-300 px-1 py-0.5 text-xs">
                        @foreach($typyOpts as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                @elseif($typInput === 'select-kraj')
                    <select x-model="tempVal" class="rounded border border-gray-300 px-1 py-0.5 text-xs">
                        <option value="">—</option>
                        @foreach($krajeOpts as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                @elseif($typInput === 'select-stav')
                    <select x-model="tempVal" class="rounded border border-gray-300 px-1 py-0.5 text-xs">
                        @foreach($stavyOpts as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                @elseif($typInput === 'textarea')
                    <textarea x-model="tempVal" rows="2" class="rounded border border-gray-300 px-1 py-0.5 text-xs w-64"></textarea>
                @else
                    <input type="{{ $typInput }}" x-model="tempVal"
                        class="rounded border border-gray-300 px-1 py-0.5 text-xs">
                @endif
                <button type="button" @click="ulozPole('{{ $pole }}')" class="text-green-600 text-sm">✓</button>
                <button type="button" @click="editPole = null" class="text-red-500 text-sm">✕</button>
            </div>
        </div>
    @endforeach
</div>
