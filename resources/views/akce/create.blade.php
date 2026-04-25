<x-layouts.app title="Nová akce — Rajón">
    <div class="max-w-3xl">
        <a href="{{ route('akce.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět na katalog</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Nová akce</h1>

        @php
            $typy = [
                'pout' => 'Pouť',
                'food_festival' => 'Food festival',
                'slavnosti' => 'Slavnosti',
                'mestske_slavnosti' => 'Městské slavnosti',
                'obrani' => 'Obraní (vinobraní/dýňobraní/bramborobraní/jablkobraní/...)',
                'trhy_jarmarky' => 'Trhy a jarmarky (farmářské/vánoční/velikonoční/jarmark)',
                'festival' => 'Festival',
                'sportovni_akce' => 'Sportovní akce',
                'koncert' => 'Koncert',
                'vystava' => 'Výstava',
                'jiny' => 'Jiný',
            ];
        @endphp

        <form method="POST" action="{{ route('akce.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-3">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Název *</label>
                <input type="text" name="nazev" required value="{{ old('nazev') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @error('nazev') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ *</label>
                    <select name="typ" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($typy as $v => $l)
                            <option value="{{ $v }}" {{ old('typ') === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stav *</label>
                    <select name="stav" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="overena" {{ old('stav', 'overena') === 'overena' ? 'selected' : '' }}>Ověřená</option>
                        <option value="navrh" {{ old('stav') === 'navrh' ? 'selected' : '' }}>Návrh</option>
                        <option value="zrusena" {{ old('stav') === 'zrusena' ? 'selected' : '' }}>Zrušená</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum od</label>
                    <input type="date" name="datum_od" value="{{ old('datum_od') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum do</label>
                    <input type="date" name="datum_do" value="{{ old('datum_do') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Místo</label>
                <input type="text" name="misto" value="{{ old('misto') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                <input type="text" name="adresa" value="{{ old('adresa') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Okres</label>
                    <input type="text" name="okres" value="{{ old('okres') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kraj</label>
                    <input type="text" name="kraj" value="{{ old('kraj') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Organizátor</label>
                <input type="text" name="organizator" value="{{ old('organizator') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input type="email" name="kontakt_email" value="{{ old('kontakt_email') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input type="text" name="kontakt_telefon" value="{{ old('kontakt_telefon') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Web</label>
                <input type="url" name="web_url" value="{{ old('web_url') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                <textarea name="popis" rows="4" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('popis') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                <textarea name="poznamka" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('poznamka') }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark">Vytvořit akci</button>
        </form>
    </div>
</x-layouts.app>
