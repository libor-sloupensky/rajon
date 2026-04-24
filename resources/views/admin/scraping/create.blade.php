<x-layouts.app title="Přidat zdroj — Rajón">
    <div class="max-w-2xl">
        <a href="{{ route('admin.scraping.index') }}" class="text-sm text-primary hover:text-primary-dark mb-4 inline-block">&larr; Zpět</a>
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Přidat zdroj</h1>

        {{-- AI analýza URL --}}
        <div x-data="zdrojAnalyzer()" class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">URL zdroje</label>
                <div class="flex gap-2">
                    <input type="url" x-model="url" placeholder="https://www.example.cz/akce"
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                    <button type="button" @click="analyzuj()" :disabled="loading || !url"
                        class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50 transition">
                        <span x-show="!loading">🤖 Analyzovat</span>
                        <span x-show="loading">Analyzuji...</span>
                    </button>
                </div>

                {{-- Výsledky analýzy --}}
                <div x-show="analyza" x-cloak class="mt-4 space-y-2 text-sm">
                    <div class="rounded bg-green-50 border border-green-200 p-3 space-y-1">
                        <p class="font-medium text-green-800">AI analýza dokončena:</p>
                        <p>CMS: <strong x-text="analyza?.cms_typ ?? 'neznámý'"></strong></p>
                        <p x-show="analyza?.sitemap_url">
                            Sitemap: <a :href="analyza?.sitemap_url" target="_blank" class="text-primary" x-text="analyza?.sitemap_url"></a>
                            <span x-show="pocetUrl > 0">(<strong x-text="pocetUrl"></strong> URL)</span>
                        </p>
                        <p x-show="analyza?.robots_url">
                            Robots.txt: <a :href="analyza?.robots_url" target="_blank" class="text-primary" x-text="analyza?.robots_url"></a>
                        </p>
                        <p x-show="analyza?.struktura?.odkazy_akci?.length">
                            Odkazů akcí nalezeno: <strong x-text="analyza?.struktura?.odkazy_akci?.length"></strong>
                        </p>
                        <p x-show="analyza?.struktura?.jsonld_events?.length">
                            ✨ JSON-LD Event nalezeno: <strong x-text="analyza?.struktura?.jsonld_events?.length"></strong>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Formulář --}}
            <form method="POST" action="{{ route('admin.scraping.store') }}" class="rounded-lg border border-gray-200 bg-white p-4 space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název</label>
                    <input type="text" name="nazev" required value="{{ old('nazev') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL</label>
                    <input type="url" name="url" required x-model="url" value="{{ old('url') }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Typ zdroje</label>
                        <select name="typ" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="katalog">Katalog akcí</option>
                            <option value="web_mesta">Web města</option>
                            <option value="email">E-mail</option>
                            <option value="excel">Excel</option>
                            <option value="manual">Manuální</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CMS</label>
                        <input type="text" name="cms_typ" x-model="analyza?.cms_typ ?? ''" placeholder="wordpress_mec"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sitemap URL</label>
                    <input type="url" name="sitemap_url" x-model="analyza?.sitemap_url ?? ''"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL vzor detailu</label>
                    <input type="text" name="url_pattern_detail" placeholder="/akce/"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <p class="text-xs text-gray-500 mt-1">Filtr pro sitemap — obsahuje-li URL tento řetězec, jde o detail akce.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frekvence (hodiny)</label>
                    <input type="number" name="frekvence_hodin" value="168" min="1"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                    <textarea name="poznamka" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></textarea>
                </div>

                <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary-dark transition">
                    Uložit zdroj
                </button>
            </form>
        </div>
    </div>

    <script>
        function zdrojAnalyzer() {
            return {
                url: '',
                loading: false,
                analyza: null,
                pocetUrl: 0,
                async analyzuj() {
                    if (!this.url) return;
                    this.loading = true;
                    this.analyza = null;

                    try {
                        const res = await fetch('{{ route("admin.scraping.analyzovat") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ url: this.url }),
                        });
                        const data = await res.json();
                        if (data.ok) {
                            this.analyza = data.analyza;
                            this.pocetUrl = data.pocet_url_v_sitemap;
                        }
                    } catch (e) {
                        alert('Chyba analýzy: ' + e.message);
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-layouts.app>
