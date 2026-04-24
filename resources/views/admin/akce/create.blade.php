<x-layouts.app title="Nová akce — Rajón">
    <div class="max-w-2xl">
        <a href="{{ route('admin.akce.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Nová akce</h1>

        <form method="POST" action="{{ route('admin.akce.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-3">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Název</label>
                <input type="text" name="nazev" required value="{{ old('nazev') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                    <select name="typ" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach(['pout' => 'Pouť', 'food_festival' => 'Food festival', 'slavnosti' => 'Slavnosti', 'vinobrani' => 'Vinobraní', 'jarmark' => 'Jarmark', 'festival' => 'Festival', 'jiny' => 'Jiný'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stav</label>
                    <select name="stav" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="navrh">Návrh</option>
                        <option value="overena">Ověřená</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum od</label>
                    <input type="date" name="datum_od" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum do</label>
                    <input type="date" name="datum_do" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Místo</label>
                <input type="text" name="misto" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                <textarea name="popis" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white">Vytvořit akci</button>
        </form>
    </div>
</x-layouts.app>
