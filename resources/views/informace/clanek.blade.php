<x-layouts.app :title="$stranka->nadpis . ' — Rajón'">
    @php $jeAdmin = Auth::user()?->jeAdmin(); @endphp

    <div class="max-w-2xl" @if($jeAdmin) x-data="clanekEditor()" @endif>
        <div class="mb-6 flex items-start justify-between gap-4">
            <h1 class="text-2xl font-extrabold text-gray-900">{{ $stranka->nadpis }}</h1>
            @if($jeAdmin)
                <button type="button" @click="edit = true" x-show="!edit" x-cloak
                        class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-500 hover:bg-gray-50 transition" title="Upravit text">
                    ✎ Upravit
                </button>
            @endif
        </div>

        {{-- Čtení --}}
        <div class="clanek" @if($jeAdmin) x-show="!edit" @endif>{!! $stranka->obsah !!}</div>

        @if($jeAdmin)
            {{-- Editace --}}
            <div x-show="edit" x-cloak>
                <input id="trix-{{ $stranka->id }}" type="hidden" value="{{ $stranka->obsah }}">
                <trix-editor input="trix-{{ $stranka->id }}"></trix-editor>
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" @click="ulozit()" :disabled="uklada"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition disabled:opacity-50">
                        <span x-show="!uklada">Uložit</span><span x-show="uklada" x-cloak>Ukládám…</span>
                    </button>
                    <button type="button" @click="edit = false"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition">Zrušit</button>
                    <span x-show="chyba" x-text="chyba" x-cloak class="text-sm text-red-600"></span>
                </div>
            </div>

            <script>
                function clanekEditor() {
                    return {
                        edit: false,
                        uklada: false,
                        chyba: '',
                        async ulozit() {
                            this.uklada = true;
                            this.chyba = '';
                            const input = document.getElementById('trix-{{ $stranka->id }}');
                            try {
                                const r = await fetch('{{ route('admin.stranky.ulozit', $stranka) }}', {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({ obsah: input.value }),
                                });
                                if (!r.ok) throw new Error('HTTP ' + r.status);
                                window.location.reload();
                            } catch (e) {
                                this.chyba = 'Uložení selhalo: ' + e.message;
                                this.uklada = false;
                            }
                        },
                    };
                }
            </script>
        @endif
    </div>
</x-layouts.app>
