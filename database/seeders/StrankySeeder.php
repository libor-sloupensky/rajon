<?php

namespace Database\Seeders;

use App\Models\Stranka;
use Illuminate\Database\Seeder;

/**
 * Naseeduje editovatelné články (Franšízanti, Jak prodávat) z aktuálního obsahu.
 * firstOrCreate → nepřepíše obsah, který admin později upraví na webu.
 */
class StrankySeeder extends Seeder
{
    public function run(): void
    {
        $fransizanti = <<<'HTML'
<h1>Reprezentace značky</h1>
<p>Buď ambasadorem značek <strong>WormUP</strong> a <strong>Grig</strong> — vystupuj, jako bys byl součástí firmy, a dělej jí dobré jméno.</p>
<h1>Registrace a zvýhodněné ceny</h1>
<ul>
<li>Zaregistruj se na <a href="https://www.gogrig.com" target="_blank" rel="noopener">gogrig.com</a> a dej nám vědět na <a href="mailto:libor@wormup.com">libor@wormup.com</a>. Následně ti přidělíme zvýhodněné franšízantské ceny.</li>
<li>Produkty se zvýhodněnou cenou najdeš zde: <a href="https://www.gogrig.com/fransiza/" target="_blank" rel="noopener">gogrig.com/fransiza</a>.</li>
<li>Objednat si můžeš i ostatní produkty — ale ty jsou za běžné velkoobchodní ceny. Ne za franšízantské.</li>
</ul>
<h1>První objednávka a splatnost</h1>
<ul>
<li>První objednávka je do <strong>15 000 Kč</strong> se splatností <strong>14 dní</strong>.</li>
<li>Další objednávky už mohou být vyšší.</li>
</ul>
<h1>Pravidlo k balíčkům 80 g</h1>
<p>Ke každým <strong>70 balíčkům</strong> si můžeš přikoupit <strong>1 balíček 80 g</strong> dle vlastního výběru. Prosíme o dodržení tohoto poměru, ať nemusíme do objednávek zasahovat.</p>
HTML;

        $jakProdavat = <<<'HTML'
<ul>
<li><strong>Zamiluj se.</strong> Nejdřív musíš Křupavým červíkům přijít na chuť sám. Pokud z nich nebudeš nadšený, zákazníky nepřesvědčíš.</li>
<li><strong>Buď aktivní a veselý.</strong> Neboj se oslovit kolemjdoucího a pozvat ho ke stánku. Když odmítne, přejdi to s úsměvem — třeba hláškou „čekáme tu na vás ;-)". Nikdy mu jeho rozhodnutí nevyčítej. Ty víš, že chutnají skvěle; jeho „fuj" je jen otázka obav a přesvědčení. To změníš pozitivním přístupem, ne bojem.</li>
<li><strong>Vzbuď zvědavost.</strong> 90 % zákazníků přijde ke stánku ze zvědavosti — chtějí si ověřit, jestli opravdu prodáváš hmyz a jestli to fakt vypadá jako červi. Pracuj s tím: „Budete překvapeni, jak dobře chutnají", „děti to milují", „je to jídlo budoucnosti".</li>
<li><strong>Začni ty.</strong> Zákazníci červíkům většinou nevěří, dokud hmyz sami neochutnali. Ochutnej první přímo před nimi — když uvidí, že jsi to přežil, sejmeš z nich jednu z obav.</li>
<li><strong>Přirovnej příchutě k něčemu známému.</strong> Např. česnekoví červíci = strážnické brambůrky, se solí = pražená slunečnice, chilli = pražená kukuřice. „Pojďte si ověřit, jestli nekecám…"</li>
<li><strong>Informuj.</strong> Zákazník potřebuje u červíků mnohem víc informací než u jakéhokoli jiného produktu. Hmyz je nová potravina a ty jako Ambasador šíříš osvětu.</li>
</ul>
<h1>Tři typy zákazníků</h1>
<ul>
<li><strong>Odmítač</strong> — naštvaně říká „fuj, to je nechutný". Na něj neplýtvej silami.</li>
<li><strong>Nerozhodný</strong> — mlčí nebo odmítá se smíchem. Sám ho těžko přesvědčíš, ale dokáže to jeho kamarád, kterého získáš jako prvního.</li>
<li><strong>Připravený</strong> — má hloubavý pohled (uvnitř se rozhoduje a váhá), přijde se podívat, je zvědavý, říká „no já nevím" a ptá se na detaily. Když se ptá, sbírá informace ke svému odvážnému kroku — pomoz mu k rozhodnutí a podpoř ho.</li>
</ul>
<ul>
<li><strong>Ovládni skupinku — najdi připraveného.</strong> Velká část kolemjdoucích skupinek má mezi sebou „připraveného". Najdi ho a zaměř se na něj. Pozor, ve skupince bývá i silný, autoritativní odmítač. Posiluj pozici připraveného a odmítače s elegancí „polituj" — tím mu vezmeš vítr z plachet; nebojuj s ním, to by ho jen posílilo. Jakmile připravený ochutná, pomůže ti přesvědčit i ostatní (věří mu) a často získá i odmítače. Často je připraveným dítě a odmítačem autoritativní rodič — ovládnout takovou skupinku je mistrovské dílo :-D</li>
<li><strong>Boř mýty.</strong> Hmyz se obecně považuje za něco nečistého a lidé si červa spojují se špínou. Změň tu představu: naši červíci jsou chovaní v čistém prostředí a krmení obilninami, mrkví a bramborami.</li>
<li><strong>Čistota, image, branding.</strong> Stánek i ty musíte vypadat hezky. Drž čistotu na stánku a buď upravený — zákazníci ti pak víc důvěřují. Nezapomeň na brandování: lidé se hmyzu bojí a „vystajlovaný" a čistý stánek zvýší důvěru v kvalitu produktu.</li>
<li><strong>Správné pojmenování.</strong> Produkt nazýváme vždy „Křupaví červíci", výjimečně jen „červíci". Nesprávné názvy jako „červi" nebo „sušené larvy" mohou vyvolat negativnější reakci.</li>
<li><strong>Chce nakoupit? Inspiruj ho k většímu nákupu.</strong> „Když si koupíte víc pytlíků, máte akční cenu." Nastiň využití: „Vezměte to do práce a dejte ochutnat kolegům", „vezměte to místo lahve vína na návštěvu", „je to skvělý a originální dárek za pár korun".</li>
</ul>
HTML;

        Stranka::firstOrCreate(['slug' => 'fransizanti'], [
            'nadpis' => 'Informace pro franšízanty',
            'obsah' => $fransizanti,
        ]);

        Stranka::firstOrCreate(['slug' => 'jak-prodavat'], [
            'nadpis' => 'Jak prodávat jedlý hmyz',
            'obsah' => $jakProdavat,
        ]);
    }
}
