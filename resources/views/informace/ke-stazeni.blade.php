<x-layouts.app title="Soubory ke stažení — Rajón">
    @php $jeAdmin = Auth::user()?->jeAdmin(); @endphp

    <div class="max-w-2xl space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">Soubory ke stažení</h1>
        <p class="text-sm text-gray-500">Bannery a logo pro tisk a prezentaci na akcích.</p>

        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
            @forelse($soubory as $s)
                <div x-data="{ edit: false }" class="p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-800">{{ $s->nazev }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $s->popis }}{{ $s->popis ? ' · ' : '' }}{{ $s->typ }}{{ $s->velikostText() ? ' · ' . $s->velikostText() : '' }}
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a href="{{ $s->urlKeStazeni() }}" @if($s->zdroj === 'public') download="{{ $s->download_nazev }}" @endif
                               class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                                Stáhnout
                            </a>
                            @if($jeAdmin)
                                <button type="button" @click="edit = !edit" title="Přejmenovat"
                                        class="rounded-lg border border-gray-300 px-2 py-2 text-gray-500 hover:bg-gray-50 transition">✎</button>
                                <form method="POST" action="{{ route('admin.soubory.destroy', $s) }}"
                                      onsubmit="return confirm('Opravdu smazat „{{ $s->nazev }}“?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Smazat"
                                            class="rounded-lg border border-gray-300 px-2 py-2 text-red-500 hover:bg-red-50 transition">🗑</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($jeAdmin)
                        <form method="POST" action="{{ route('admin.soubory.update', $s) }}" x-show="edit" x-cloak
                              class="mt-3 flex flex-wrap items-end gap-2 border-t border-gray-100 pt-3">
                            @csrf @method('PUT')
                            <div class="flex-1 min-w-[12rem]">
                                <label class="block text-xs text-gray-500">Název</label>
                                <input type="text" name="nazev" value="{{ $s->nazev }}" required
                                       class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                            </div>
                            <div class="flex-1 min-w-[12rem]">
                                <label class="block text-xs text-gray-500">Popis</label>
                                <input type="text" name="popis" value="{{ $s->popis }}"
                                       class="w-full rounded border border-gray-300 px-2 py-1 text-sm">
                            </div>
                            <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-dark transition">Uložit</button>
                            <button type="button" @click="edit = false" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition">Zrušit</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="p-4 text-sm text-gray-500">Zatím žádné soubory.</div>
            @endforelse
        </div>

        @if($jeAdmin)
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                <h2 class="mb-3 text-base font-semibold text-gray-800">Nahrát nový soubor</h2>
                <form method="POST" action="{{ route('admin.soubory.store') }}" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <div class="flex flex-wrap gap-2">
                        <input type="text" name="nazev" placeholder="Název (zobrazí se)" required
                               class="flex-1 min-w-[12rem] rounded border border-gray-300 px-3 py-2 text-sm">
                        <input type="text" name="popis" placeholder="Krátký popis (volitelné)"
                               class="flex-1 min-w-[12rem] rounded border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <input type="file" name="soubor" required
                           class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-primary-dark">
                    <p class="text-xs text-gray-400">Max 30 MB.</p>
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">Nahrát</button>
                </form>
                @error('soubor') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('nazev') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif
    </div>
</x-layouts.app>
