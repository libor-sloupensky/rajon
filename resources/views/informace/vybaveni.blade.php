<x-layouts.app title="Vybavení na akci — Rajón">
    @php
        $sekce = [
            'Prezentace' => [
                ['t' => 'Laminovaný ceník na stůl', 'n' => 'hlavně pokud máme akci 3+1 a podobně'],
                ['t' => 'Cedulka „ochutnávka zdarma“'],
                ['t' => 'Označení provozovny', 'n' => 'tvůj název firmy/osoby, adresa, IČ atd.'],
                ['t' => 'FAQ — mít alespoň nastudované'],
                ['t' => 'Stánek'],
                ['t' => 'Prodejní stůl'],
                ['t' => 'Banner na stůl, případně na čelo stánku'],
            ],
            'Zázemí' => [
                ['t' => 'Box kapesníčků / vlhčené ubrousky'],
                ['t' => 'Nůžky'],
                ['t' => 'Nůž'],
                ['t' => 'Kobercová lepicí páska (ideálně bílá)', 'n' => 'NE klasická izolepa — ta poškozuje vybavení'],
                ['t' => 'Propisky/cenovky'],
                ['t' => 'Lepicí guma (žvýkačka)'],
                ['t' => 'Paragony', 'n' => 'prodejce je povinen na žádost vystavit daňový doklad; případně stačí poslat elektronicky, např. e-mailem'],
                ['t' => 'Igelitové či papírové sáčky', 'n' => 'někdy je zákazníci potřebují'],
                ['t' => 'Barová židlička', 'n' => 'nízkou nedoporučujeme, budeš pak zákazníkovi daleko'],
                ['t' => 'Drobné na vrácení + kasírka'],
                ['t' => 'Platební terminál', 'n' => 'nebo si aspoň zprovoznit platby přes QR kód'],
            ],
            'Zboží a ochutnávky' => [
                ['t' => 'Muffinové košíčky', 'odkaz' => 'https://www.vikpap.cz/wimex-72624-cukrarsky-kosicek-prumer-24-18-mm-bily-1000-ks', 'n' => 'na ochutnávky, nebo můžeš dávat jen na dlaň, ale doporučuji alespoň nějaké košíčky mít. Máme skladem a na požádání ti je přidáme k objednávce'],
                ['t' => 'Koš na stůl'],
                ['t' => 'Lžičky', 'n' => 'na podávání červíků'],
                ['t' => 'Uzavíratelné misky na dávkování ochutnávek', 'n' => 'nedoporučujeme podávat z pytlíků 80 g, protože častým otevíráním mohou navlhnout. Přesyp si to do menších uzavíratelných skleniček/kořenek — v Pepco za pár korun'],
                ['t' => 'Vzorky na ochutnávku 80 g'],
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
                                    @if(!empty($p['odkaz']))
                                        <a href="{{ $p['odkaz'] }}" target="_blank" rel="noopener" class="font-semibold text-primary hover:underline">{{ $p['t'] }}</a>
                                    @else
                                        {{ $p['t'] }}
                                    @endif
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
