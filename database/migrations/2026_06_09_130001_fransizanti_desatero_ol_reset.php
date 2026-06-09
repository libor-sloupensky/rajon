<?php

use App\Models\Stranka;
use Illuminate\Database\Migrations\Migration;

/**
 * Reset obsahu Franšízantů na čisté <ol> seznamy. Čísla Desatera dělá CSS
 * (průběžný counter v .clanek ol), takže NESMÍ být v textu — Trix by je jinak
 * převedl zpět na seznam a každý by začínal od 1.
 *
 * Zahrnuje webový edit z bodu 10 (skládací stolky / stánek 2×2 m / 1500 Kč).
 */
return new class extends Migration
{
    public function up(): void
    {
        $obsah = <<<'HTML'
<p>Buď ambasadorem značek <strong>WormUP</strong> a <strong>Grig</strong> — vystupuj, jako bys byl součástí firmy, a dělej jí dobré jméno.</p>
<h1>Registrace a zvýhodněné ceny</h1>
<ul>
<li>Zaregistruj se na <a href="https://www.gogrig.com" target="_blank" rel="noopener">gogrig.com</a> a dej nám vědět na <a href="mailto:libor@wormup.com">libor@wormup.com</a>. Následně ti přidělíme zvýhodněné franšízantské ceny.</li>
<li>Produkty se zvýhodněnou cenou najdeš zde: <a href="https://www.gogrig.com/fransiza/" target="_blank" rel="noopener">gogrig.com/fransiza</a>.</li>
<li>Objednat si můžeš i ostatní produkty — ale ty jsou za běžné velkoobchodní ceny. Ne za franšízantské.</li>
</ul>
<h1>Pravidlo k balíčkům 80 g</h1>
<p>Ke každým <strong>70 balíčkům</strong> si můžeš přikoupit <strong>1 balíček 80 g</strong> dle vlastního výběru. Prosíme o dodržení tohoto poměru, ať nemusíme do objednávek zasahovat.</p>
<h1>Desatero franšízanta WormUP &amp; Grig</h1>
<p>Pravidla, kterými se řídí prodejce naší značky.</p>
<h2>Tvůj stánek je tvář značky</h2>
<ol>
<li><strong>Stánek měj vždy řádně označený.</strong> Loga, bannery a firemní grafika WormUP a Grig musí být viditelné, čisté a v dobrém stavu.</li>
<li><strong>Čistota, pořádek, hygiena.</strong> Stánek udržuj uklizený a vybavení čisté, grafiku bez poškození — z dálky musí být poznat, že jde o profesionální stánek značky. Dodržuj hygienické předpisy, sleduj doby spotřeby a dbej na správné skladování zboží (zejména čokoládové produkty v letních horkách).</li>
</ol>
<h2>Jak prodáváš</h2>
<ol>
<li><strong>Aktivně oslovuj zákazníky.</strong> Pasivní stánkař na akci neuspěje. Nabízej ochutnávky, vysvětluj, vyprávěj o produktu. Ochutnávka zdarma je tvůj nejsilnější prodejní nástroj.</li>
<li><strong>Reprezentuj značku slušně a profesionálně.</strong> Nejen vůči zákazníkům, ale i vůči organizátorům akcí, sousedním stánkařům a v online prostoru (sociální sítě, fotky z akce apod.).</li>
</ol>
<h2>Co nesmíš</h2>
<ol>
<li><strong>Žádný online prodej.</strong> E-shop, marketplace ani prodej přes sociální sítě. Tvoje doména je živý kontakt se zákazníkem na akcích.</li>
<li><strong>Velkoobchod a maloobchod jen s naším souhlasem.</strong> Pokud projeví zájem prodejna, kavárna, restaurace nebo distributor o pravidelný odběr, kontaktuj nás. Nedohaduj nic na vlastní pěst.</li>
<li><strong>Žádná konkurence.</strong> Po celou dobu spolupráce a 1 rok po jejím ukončení neprodávej a nepropaguj produkty z jedlého hmyzu od jiných výrobců — ať už jako stánkař, distributor, nebo přes online kanály.</li>
</ol>
<h2>Finance a vybavení</h2>
<ol>
<li><strong>Kauce 10 000 Kč.</strong> Skládá se před první dodávkou zboží na účet WormUP. Vrací se po ukončení spolupráce, vrácení vybavení a vyrovnání všech závazků.</li>
<li><strong>Faktury plať včas.</strong> Splatnost je 14 dní od vystavení. První objednávka je do 15 000 Kč, další už mohou být vyšší. Spolehlivost v placení je základ vzájemné důvěry — a v případě prodlení nepřijímáme další objednávky.</li>
<li><strong>Zapůjčené vybavení = tvoje odpovědnost.</strong> Zacházej s ním pečlivě. Při poškození, ztrátě nebo nevrácení se náklady strhávají z kauce. Při ukončení spolupráce vrať vybavení v původním stavu (kromě běžného opotřebení). Zapůjčíme ti skládací stolky a bílý stánek 2×2 m. Za zapůjčený stánek bude účtováno 1 500 Kč měsíčně bez DPH.</li>
</ol>
<p><strong>Smysl těchto pravidel ve zkratce:</strong> Pracuješ pod naší značkou — tvoje úspěšnost je naše úspěšnost, tvoje chyba je naše chyba. Dodržuj pravidla, prezentuj produkt s nadšením, drž se kvality — a vše ostatní se postará samo.</p>
HTML;

        Stranka::where('slug', 'fransizanti')->update(['obsah' => $obsah]);
    }

    public function down(): void
    {
        // jednosměrné
    }
};
