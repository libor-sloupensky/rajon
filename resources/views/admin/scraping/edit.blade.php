<x-layouts.app title="Upravit zdroj — Rajón">
    <div class="max-w-2xl">
        <a href="{{ route('admin.scraping.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Upravit zdroj: {{ $zdroj->nazev }}</h1>

        <form method="POST" action="{{ route('admin.scraping.update', $zdroj) }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Název</label>
                <input type="text" name="nazev" required value="{{ old('nazev', $zdroj->nazev) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                <input type="url" name="url" required value="{{ old('url', $zdroj->url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                    <select name="typ" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach(['katalog' => 'Katalog', 'web_mesta' => 'Web města', 'email' => 'E-mail', 'excel' => 'Excel', 'manual' => 'Manuální'] as $v => $l)
                            <option value="{{ $v }}" {{ $zdroj->typ === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CMS</label>
                    <input type="text" name="cms_typ" value="{{ old('cms_typ', $zdroj->cms_typ) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sitemap URL</label>
                <input type="url" name="sitemap_url" value="{{ old('sitemap_url', $zdroj->sitemap_url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">URL vzor detailu</label>
                <input type="text" name="url_pattern_detail" value="{{ old('url_pattern_detail', $zdroj->url_pattern_detail) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frekvence (h)</label>
                    <input type="number" name="frekvence_hodin" value="{{ old('frekvence_hodin', $zdroj->frekvence_hodin) }}" min="1" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stav</label>
                    <select name="stav" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach(['aktivni' => 'Aktivní', 'neaktivni' => 'Neaktivní', 'chyba' => 'Chyba'] as $v => $l)
                            <option value="{{ $v }}" {{ $zdroj->stav === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                <textarea name="poznamka" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('poznamka', $zdroj->poznamka) }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                Uložit změny
            </button>
        </form>
    </div>
</x-layouts.app>
