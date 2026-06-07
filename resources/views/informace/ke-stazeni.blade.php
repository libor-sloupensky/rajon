<x-layouts.app title="Soubory ke stažení — Rajón">
    @php
        $soubory = [
            [
                'nazev' => 'Banner 190 × 30 cm',
                'popis' => 'Banner na stůl / čelo stánku',
                'soubor' => 'soubory/banner-190x30.pdf',
                'stahnout' => 'WormUP-banner-190x30.pdf',
                'typ' => 'PDF',
                'velikost' => '2,9 MB',
            ],
            [
                'nazev' => 'Banner na stánek 190 × 90 cm',
                'popis' => 'Velký banner na stánek',
                'soubor' => 'soubory/banner-stanek-190x90.pdf',
                'stahnout' => 'WormUP-banner-stanek-190x90.pdf',
                'typ' => 'PDF',
                'velikost' => '3,9 MB',
            ],
            [
                'nazev' => 'Logo WormUP',
                'popis' => 'Logo pro tisk i web',
                'soubor' => 'soubory/logo-wormup.jpg',
                'stahnout' => 'logo-wormup.jpg',
                'typ' => 'JPG',
                'velikost' => '73 kB',
            ],
        ];
    @endphp

    <div class="max-w-2xl space-y-4">
        <h1 class="text-2xl font-bold text-gray-900">Soubory ke stažení</h1>
        <p class="text-sm text-gray-500">Bannery a logo pro tisk a prezentaci na akcích.</p>

        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
            @foreach($soubory as $s)
                <div class="flex items-center justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-800">{{ $s['nazev'] }}</div>
                        <div class="text-xs text-gray-500">{{ $s['popis'] }} · {{ $s['typ'] }} · {{ $s['velikost'] }}</div>
                    </div>
                    <a href="{{ asset($s['soubor']) }}" download="{{ $s['stahnout'] }}"
                       class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark transition">
                        Stáhnout
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
