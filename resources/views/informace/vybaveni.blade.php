<x-layouts.app title="Vybavení na akci — Rajón">
    @php
        $sekce = [
            'Prezentace' => [
                ['t' => 'Laminovaný ceník na stůl'],
                ['t' => 'Laminovaná „ochutnávka zdarma“ na stůl', 'n' => 'případně stačí na banneru'],
                ['t' => 'Označení provozovny', 'n' => 'prodejní místo musí být řádně označené, jinak hrozí prodejci pokuta'],
                ['t' => 'FAQ — mít alespoň nastudované'],
                ['t' => 'Stánek'],
                ['t' => 'Prodejní stůl'],
                ['t' => 'Banner na stůl, případně na čelo stánku'],
                ['t' => 'Slevové kupóny'],
            ],
            'Zázemí' => [
                ['t' => 'Box kapesníčků / vlhčené ubrousky'],
                ['t' => 'Nůžky'],
                ['t' => 'Nůž'],
                ['t' => 'Kobercová lepicí páska (ideálně bílá)', 'n' => 'NE klasická izolepa — ta poškozuje vybavení'],
                ['t' => 'Propisky'],
                ['t' => 'Lepicí guma (žvýkačka)'],
                ['t' => 'Paragony', 'n' => 'prodejce je povinen na žádost vystavit daňový doklad; případně stačí poslat elektronicky, např. e-mailem'],
                ['t' => 'Igelitové sáčky'],
                ['t' => 'Židlička'],
                ['t' => 'Drobné na vrácení + kasírka'],
                ['t' => 'Platební terminál', 'n' => 'nebo aspoň zprovoznit platby přes QR kód'],
            ],
            'Zboží a ochutnávky' => [
                ['t' => 'Muffinové košíčky na ochutnávky'],
                ['t' => 'Obrázky koření k ochutnávkám'],
                ['t' => 'Koš na stůl'],
                ['t' => 'Lžičky'],
                ['t' => 'Uzavíratelné misky na dávkování ochutnávek'],
                ['t' => 'Vzorky na ochutnávku'],
                ['t' => 'Zboží na prodej'],
            ],
        ];
    @endphp

    <div class="max-w-2xl">
        <h1 class="mb-1 text-2xl font-bold text-gray-900">Vybavení na akci</h1>
        <p class="mb-7 text-sm text-gray-500">Checklist, ať na stánku nic nechybí.</p>

        <div class="space-y-7">
            @foreach($sekce as $nazev => $polozky)
                <section>
                    <h2 class="mb-2.5 text-base font-bold text-gray-900">{{ $nazev }}</h2>
                    <ul class="space-y-2 text-[15px] leading-relaxed text-gray-700">
                        @foreach($polozky as $p)
                            <li class="flex gap-2.5">
                                <span class="mt-px text-primary">☐</span>
                                <span>
                                    {{ $p['t'] }}
                                    @if(!empty($p['n']))
                                        <span class="text-gray-500">— {{ $p['n'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
