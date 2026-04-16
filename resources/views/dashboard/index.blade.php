<x-layouts.app title="Dashboard — Rajón">
    <h1 class="mb-6 text-2xl font-bold text-gray-800">Dashboard</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Nadcházející akce --}}
        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-700">Nadcházející akce</h2>
            @if($nadchazejiciAkce->isEmpty())
                <p class="text-sm text-gray-500">Žádné nadcházející akce.</p>
            @else
                <div class="space-y-3">
                    @foreach($nadchazejiciAkce as $akce)
                        <a href="{{ route('akce.show', $akce) }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-800">{{ $akce->nazev }}</h3>
                                    <p class="text-sm text-gray-500">{{ $akce->misto }}</p>
                                </div>
                                <div class="text-right text-sm">
                                    <div class="font-medium" style="color: var(--c-primary);">{{ $akce->datum_od?->format('j. n. Y') }}</div>
                                    @if($akce->typ !== 'jiny')
                                        <span class="inline-block mt-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium" style="color: var(--c-primary);">{{ str_replace('_', ' ', $akce->typ) }}</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Moje rezervace --}}
        <div>
            <h2 class="mb-4 text-lg font-semibold text-gray-700">Moje rezervace</h2>
            @if($mojeRezervace->isEmpty())
                <p class="text-sm text-gray-500">Zatím nemáte žádné rezervace.</p>
            @else
                <div class="space-y-3">
                    @foreach($mojeRezervace as $rez)
                        <a href="{{ route('akce.show', $rez->akce) }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-800">{{ $rez->akce->nazev }}</h3>
                                    <p class="text-sm text-gray-500">{{ $rez->akce->misto }} — {{ $rez->akce->datum_od?->format('j. n. Y') }}</p>
                                </div>
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $rez->stav === 'potvrzena' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                    {{ $rez->stav }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
