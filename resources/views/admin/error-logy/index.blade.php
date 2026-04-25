<x-layouts.app title="Error logy — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Error logy</h1>
        <p class="text-sm text-gray-500">{{ count($logy) }} souborů · denní rotace, retention 14 dní</p>
    </div>

    @if(empty($logy))
        <p class="text-gray-500">Žádné log soubory.</p>
    @else
        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Soubor</th>
                        <th class="text-right px-4 py-2">Velikost</th>
                        <th class="text-left px-4 py-2">Upraveno</th>
                        <th class="text-right px-4 py-2">Akce</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($logy as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs">{{ $log['nazev'] }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ number_format($log['velikost'] / 1024, 1, ',', ' ') }} kB</td>
                            <td class="px-4 py-2 text-gray-500">{{ $log['upraveno']->format('j. n. Y H:i:s') }} · {{ $log['upraveno']->diffForHumans() }}</td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex gap-2 justify-end">
                                    <a href="{{ route('admin.error-logy.show', $log['nazev']) }}" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Zobrazit</a>
                                    <a href="{{ route('admin.error-logy.raw', $log['nazev']) }}" target="_blank" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Plain text</a>
                                    <a href="{{ route('admin.error-logy.download', $log['nazev']) }}" class="rounded border border-gray-300 px-2 py-1 text-xs text-gray-700 hover:bg-gray-50">Stáhnout</a>
                                    <form method="POST" action="{{ route('admin.error-logy.destroy', $log['nazev']) }}"
                                        onsubmit="return confirm('Opravdu smazat {{ $log['nazev'] }}?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-300 px-2 py-1 text-xs text-red-600 hover:bg-red-50">Smazat</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-4 text-xs text-gray-500">
            <strong>Plain text URL</strong> ti vrátí poslední ~200 kB v čistém textu — užitečné když chceš
            obsah poslat AI: <code class="font-mono bg-gray-100 px-1 rounded">/admin/error-logy/&lt;soubor&gt;/raw?bytes=500000</code>
        </p>
    @endif
</x-layouts.app>
