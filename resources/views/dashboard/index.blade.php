<x-layouts.app title="Dashboard — Rajón">
    <h1 class="mb-6 text-2xl font-bold text-gray-800">Moje rezervace</h1>

    @if($budouci->isEmpty() && $uplynule->isEmpty())
        <p class="text-sm text-gray-500">
            Zatím nemáte žádné rezervace. Procházejte
            <a href="{{ url('/akce') }}" class="text-primary hover:underline">katalog akcí</a>
            a přidejte si je do kalendáře.
        </p>
    @endif

    @php
        $renderRezervace = function ($r) {
            // Helper pro vykreslení jedné rezervace
        };
    @endphp

    {{-- Budoucí akce --}}
    @if($budouci->isNotEmpty())
        <section class="mb-8 max-w-3xl">
            <h2 class="mb-3 text-lg font-semibold text-gray-700">
                Budoucí <span class="text-sm font-normal text-gray-500">({{ $budouci->count() }})</span>
            </h2>
            <div class="space-y-3">
                @foreach($budouci as $rez)
                    <a href="{{ url('/akce') }}" class="block rounded-lg border border-gray-200 bg-white p-4 hover:border-primary transition">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-medium text-gray-800">{{ $rez->akce->nazev }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $rez->akce->datum_od?->format('j. n. Y') }}
                                    @if($rez->akce->datum_do && $rez->akce->datum_do->ne($rez->akce->datum_od))
                                        — {{ $rez->akce->datum_do->format('j. n. Y') }}
                                    @endif
                                    @if($rez->akce->misto) · {{ $rez->akce->misto }} @endif
                                </p>
                            </div>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium shrink-0
                                {{ $rez->stav === 'potvrzena' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $rez->stav }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Uplynulé akce --}}
    @if($uplynule->isNotEmpty())
        <section class="max-w-3xl">
            <h2 class="mb-3 text-lg font-semibold text-gray-700">
                Uplynulé <span class="text-sm font-normal text-gray-500">({{ $uplynule->count() }})</span>
            </h2>
            <div class="space-y-3">
                @foreach($uplynule as $rez)
                    <a href="{{ url('/akce?vse=1') }}" class="block rounded-lg border border-gray-200 bg-gray-50 p-4 hover:border-gray-400 transition opacity-80">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-medium text-gray-700">{{ $rez->akce->nazev }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $rez->akce->datum_od?->format('j. n. Y') }}
                                    @if($rez->akce->datum_do && $rez->akce->datum_do->ne($rez->akce->datum_od))
                                        — {{ $rez->akce->datum_do->format('j. n. Y') }}
                                    @endif
                                    @if($rez->akce->misto) · {{ $rez->akce->misto }} @endif
                                </p>
                            </div>
                            <span class="inline-block rounded-full bg-gray-100 text-gray-500 px-2 py-0.5 text-xs font-medium shrink-0">
                                proběhla
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
