<x-layouts.app title="Dashboard — Rajón">
    <h1 class="mb-6 text-2xl font-bold text-gray-800">Dashboard</h1>

    <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-700">Moje rezervace</h2>
        @if($mojeRezervace->isEmpty())
            <p class="text-sm text-gray-500">Zatím nemáte žádné rezervace. Procházejte <a href="{{ url('/akce') }}" class="text-primary hover:underline">katalog akcí</a> a přidejte si je do kalendáře.</p>
        @else
            <div class="space-y-3 max-w-3xl">
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
</x-layouts.app>
