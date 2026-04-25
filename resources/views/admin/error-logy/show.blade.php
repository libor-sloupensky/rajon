<x-layouts.app title="{{ $soubor }} — Rajón" :fullWidth="true">
    <div class="px-4 py-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <a href="{{ route('admin.error-logy.index') }}" class="text-sm text-primary hover:text-primary-dark">&larr; Zpět</a>
                <h1 class="text-xl font-bold text-gray-800 inline-block ml-3">{{ $soubor }}</h1>
                <span class="ml-3 text-sm text-gray-500">
                    {{ number_format($velikost / 1024, 1, ',', ' ') }} kB ·
                    zobrazeno posledních {{ number_format($tail_bytes / 1024, 0, ',', ' ') }} kB
                </span>
            </div>
            <div class="flex gap-2">
                <a href="?bytes=500000" class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">Posledních 500 kB</a>
                <a href="?bytes={{ $velikost }}" class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">Celý soubor</a>
                <a href="{{ route('admin.error-logy.raw', $soubor) }}?bytes={{ $tail_bytes }}" target="_blank"
                    class="rounded border border-primary px-3 py-1.5 text-xs text-primary hover:bg-primary/10">Plain text</a>
                <a href="{{ route('admin.error-logy.download', $soubor) }}" class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50">Stáhnout</a>
            </div>
        </div>

        <pre class="bg-gray-900 text-gray-100 text-xs p-4 rounded overflow-auto" style="max-height: calc(100vh - 10rem); white-space: pre-wrap;">{{ $obsah }}</pre>
    </div>
</x-layouts.app>
