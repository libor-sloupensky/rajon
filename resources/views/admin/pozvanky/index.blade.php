<x-layouts.app title="Pozvánky — Rajón">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pozvánky ({{ $pozvanky->total() }})</h1>
        <a href="{{ route('admin.pozvanky.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
            + Nová pozvánka
        </a>
    </div>

    @if($pozvanky->isEmpty())
        <p class="text-gray-500">Žádné pozvánky zatím nebyly vytvořeny.</p>
    @else
        <div class="space-y-2">
            @foreach($pozvanky as $p)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-800">{{ $p->email }}</span>
                                @if($p->jmeno || $p->prijmeni)
                                    <span class="text-sm text-gray-500">({{ trim("{$p->jmeno} {$p->prijmeni}") }})</span>
                                @endif
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($p->stav === 'cekajici')
                                        {{ $p->plati_do?->isPast() ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}
                                    @elseif($p->stav === 'prijata') bg-green-100 text-green-700
                                    @elseif($p->stav === 'zrusena') bg-gray-100 text-gray-700
                                    @else bg-red-100 text-red-700
                                    @endif">
                                    {{ $p->plati_do?->isPast() && $p->stav === 'cekajici' ? 'expirovaná' : $p->stav }}
                                </span>
                                <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">{{ $p->role }}</span>
                            </div>
                            <div class="text-xs text-gray-500 space-x-3">
                                @if($p->pozval)
                                    <span>Pozval: {{ $p->pozval->celejmeno() }}</span>
                                @endif
                                <span>Vytvořeno: {{ $p->vytvoreno?->format('j. n. Y H:i') }}</span>
                                @if($p->plati_do)
                                    <span>Platí do: {{ $p->plati_do->format('j. n. Y H:i') }}</span>
                                @endif
                                @if($p->prijata_v)
                                    <span class="text-green-600">Přijata: {{ $p->prijata_v->format('j. n. Y H:i') }}</span>
                                @endif
                            </div>
                            @if($p->stav === 'cekajici' && $p->jePlatna())
                                <div class="mt-2">
                                    <input type="text" readonly value="{{ $p->url() }}"
                                        class="w-full text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 font-mono"
                                        onclick="this.select()">
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-2 shrink-0 ml-4">
                            @if($p->stav === 'cekajici')
                                <form method="POST" action="{{ route('admin.pozvanky.resend', $p) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                                        Znovu odeslat
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.pozvanky.destroy', $p) }}"
                                    onsubmit="return confirm('Opravdu zrušit pozvánku?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50">
                                        Zrušit
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $pozvanky->links() }}</div>
    @endif
</x-layouts.app>
