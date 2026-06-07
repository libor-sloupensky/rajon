<x-layouts.app title="Časté dotazy (FAQ) — Rajón">
    @php
        $faq = [
            [
                'q' => 'Co je to za červa?',
                'a' => ['Jde o larvu potemníka moučného (Tenebrio molitor), přezdívanou také „moučný červ“. Potemník moučný má čtyři vývojová stádia: vajíčko, larvu (červa), kuklu a brouka. Ke zpracování je nejvhodnější právě larva.'],
            ],
            [
                'q' => 'Moučný červ — to máme doma ve špajzu, ne?',
                'a' => ['Doma v mouce míváme spíš zavíječe moučného. Ten dělá pavučinky a v dospělosti je z něj mol. Potemník je brouk a v domácnostech se vyskytuje jen výjimečně. U nás v ČR ho ale najdeme ve volné přírodě.'],
            ],
            [
                'q' => 'To ho chytáte na louce?',
                'a' => ['Potemníka moučného pro naši výrobu chováme v plastových přepravkách na hmyzí farmě na Vysočině. Krmíme ho extrudovanými obilovinami, bramborami a mrkví — dá se říct, že nežere nic, co by nebylo vhodné i pro člověka.'],
            ],
            [
                'q' => 'To se jí živé?',
                'a' => ['Stejně jako nejíme kuře zaživa, moderní člověk nejí ani živý hmyz. Larvy velmi rychle usmrtíme a poté tepelně upravíme, takže jsou připravené k okamžité spotřebě. Jíst živý hmyz není pochoutka, ale adrenalin — proto považujeme usmrcení a tepelnou úpravu za jedinou správnou cestu.'],
            ],
            [
                'q' => 'Nepřijdou tím sušením o vitamíny?',
                'a' => ['Je potřeba si uvědomit, že jde o maso — a maso téměř vždy tepelně upravujeme, aniž bychom si tuto otázku kladli. Živočišné produkty jíme především pro jejich výživové hodnoty a chuť. Tepelnou úpravou bílkovin dochází k tzv. denaturaci, čímž se stávají stravitelnějšími a chutnějšími. Stejný proces probíhá třeba i při zbělání smažených vajíček.'],
            ],
            [
                'q' => 'K čemu se to hodí? Jak se to jí?',
                'a' => ['Křupaví červíci jsou pochutina podobně jako třeba chipsy. Doporučujeme je jíst jen tak samotné — tak chutnají nejlépe. Někdo si je ale dává na chleba se sádlem, posype jimi salát nebo je přidá do polévky místo krutonů. Do vlhké stravy je vhodné je přidat až těsně před podáváním, jinak přijdete o ten báječný prožitek křupavosti.'],
            ],
            [
                'q' => 'Jaké mají výživové hodnoty?',
                'a' => ['Křupaví červíci obsahují bezmála 60 % proteinu a téměř 30 % tuků, kvalitativně podobných těm rybím. Díky tomu jsou velmi výživní.'],
            ],
            [
                'q' => 'Kolik toho musím sníst, abych se najedl?',
                'a' => ['Jde především o pochoutku, ne o hlavní jídlo. Výživově jsou velmi vydatní, ale žaludek jen tak nezaplní. Jsme zvyklí mít v žaludku nějaký objem, a ten je třeba doplnit jinými potravinami. Berme proto křupavé červíky jako originální pochutinu a skvělý zážitek.'],
            ],
            [
                'q' => 'To je smažené?',
                'a' => ['Ne. Nejprve je usmrtíme ve vroucí vodě, usušíme horkým vzduchem a pak okořeníme. Není nutné přidávat žádné další tuky, které by čistotu křupavých červíků znehodnocovaly.'],
            ],
            [
                'q' => 'Jak to chutná?',
                'a' => ['Nejčastěji to lidé připodobňují k chipsům, oříškům, pražené kukuřici nebo ke škvarkům. Není se třeba obávat, že by nám zkazili chuť — naši křupaví červíci zachutnají téměř všem.'],
            ],
            [
                'q' => 'Jak to, že je to duté?',
                'a' => ['Oproti savcům a dalším obratlovcům má hmyz tzv. exoskelet, tedy vnější kostru. Když ho vysušíme, odstraněním vody se jeho objem zmenší téměř o dvě třetiny — schránka vyschne od svého středu, a proto se zdá dutá.'],
            ],
            [
                'q' => 'Proč se tomu říká potravina budoucnosti?',
                'a' => ['Planeta je naším hospodařením velmi přetěžovaná, v potravinářství především masnou výrobou. Hmyz oproti skotu spotřebuje tisícinu vody a mnohonásobně méně krmiva a nezatěžuje planetu vysokými emisemi CO₂. Navíc nemá takovou inteligenci jako třeba prase, jehož chov ve stísněných podmínkách a zabíjení jsou neetické.'],
            ],
            [
                'q' => 'Proč je to tak drahé, když je to potravina budoucnosti?',
                'a' => ['Hmyz bude do dvaceti let levnější než kuřecí maso, teď je ale dražší než hovězí. Důvodů je víc, ale ve zkratce jde o novou, dosud nezavedenou potravinu. Je nákladné získávat zkušenosti s výrobou i chovem metodou pokus-omyl, jednat se zákonodárci o bezpečnosti výroby a chovat hmyz v malém pomocí manuální výroby. Největší část ceny ale tvoří snaha přesvědčit společnost, aby hmyz vůbec poprvé zkusila. Naším cílem je změnit myšlení většinové společnosti a nastartovat hmyzí potravinářství — proto víc než třetina ceny každého pytlíku jde právě do posilování povědomí.'],
            ],
            [
                'q' => 'Obsahuje to chitin, který je prý pro člověka nebezpečný.',
                'a' => [
                    'Hmyz obsahuje chitin, ze kterého je složený jeho exoskelet (vnější kostra). Chitin najdeme i v houbách nebo třešních, takže jde o látku, se kterou má naše tělo zkušenost. Nejenže neškodí, ale podobně jako vláknina napomáhá trávení — což potvrzují vědecké experimenty. To nic nemění na tom, že může být alergenem pro lidi citlivé na korýše.',
                    'Dezinformace o nebezpečnosti chitinu se bohužel šíří po internetu, většinou z úst odpůrců EU — kteří často také tvrdí, že se nás EU snaží cíleně vyhladit nebo zotročit. Tato tvrzení nejsou pravdivá (neexistuje žádná studie potvrzující nebezpečnost chitinu) a hmyz v tomto boji proti EU berou jen jako obětního beránka.',
                ],
            ],
            [
                'q' => 'Jaké jsou naše hlavní výhody před konkurencí?',
                'a' => ['Ve WormUP děláme hmyz tak čistě, jak to jen jde — proto ho sušíme pouze horkým vzduchem, ne mikrovlnami jako konkurence. Přidáváme jen minimum koření a hlavně žádné glutamáty ani „éčka“. Konkurenční produkty jako sýroví nebo slaninoví červíci a cvrčci bývají plní glutamátu, což nepovažujeme za zdravé. Naším cílem je, aby byl hmyz pochoutkou i pro děti — a s glutamátem to nejde.'],
            ],
        ];
    @endphp

    <div class="max-w-3xl">
        <h1 class="mb-1 text-2xl font-bold text-gray-800">Časté dotazy</h1>
        <p class="mb-5 text-sm text-gray-500">Vše, co se hodí vědět o křupavých červících — pro klidné odpovědi zákazníkům.</p>

        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white px-5">
            @foreach($faq as $item)
                <div class="py-4">
                    <h2 class="mb-1.5 flex gap-2 text-[15px] font-semibold text-gray-800">
                        <span class="text-primary">{{ $loop->iteration }}.</span>
                        <span>{{ $item['q'] }}</span>
                    </h2>
                    @foreach($item['a'] as $odstavec)
                        <p class="mb-2 pl-5 text-sm leading-relaxed text-gray-600 last:mb-0">{{ $odstavec }}</p>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
