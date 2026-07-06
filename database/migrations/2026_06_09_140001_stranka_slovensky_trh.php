<?php

use App\Models\Stranka;
use Illuminate\Database\Migrations\Migration;

/**
 * Nová editovatelná stránka „Slovenský trh" — průvodce prodejem jedlého hmyzu na SK.
 * firstOrCreate → nepřepíše pozdější webové editace.
 */
return new class extends Migration
{
    public function up(): void
    {
        $obsah = <<<'HTML'
<p>Stručný průvodce pro prodejce – co si zařídit před začátkem.</p>
<h1>1. Jaký hmyz a od kdy</h1>
<ul>
<li><strong>Larvy potemníka moučného</strong> (Tenebrio molitor): <strong>od 22. 6. 2026 všeobecné povolení</strong> – smí prodávat kdokoli, pokud jsou výrobky v souladu se specifikací. Naše výrobky jsou v souladu a mají slovenské označení.</li>
<li><strong>Kobylky</strong> (Locusta migratoria / sťahovavá kobylka): povolená nová potravina (nař. EÚ 2021/1975), ale s <strong>výhradní autorizací (exkluzivitou) pro Fair Insects B.V. do 5. 12. 2026</strong>.</li>
<li><strong>Cvrčci</strong> (Acheta domesticus / svrček domový): povolená nová potravina (nař. EÚ 2022/188), ale s <strong>výhradní autorizací (exkluzivitou) pro Fair Insects B.V. do ~3. 3. 2027</strong>.</li>
</ul>
<p><strong>Poznámka ke kobylkám a cvrčkům:</strong> Úředníci na Slovensku obvykle neznají složitost problematiky prodeje jedlého hmyzu do detailu. Hmyz je totiž možné prodávat i před uplynutím exkluzivity, ale pouze se souhlasem společnosti „Fair Insects B.V.", který nemáme. Jsme ale přesvědčeni, že to žádný úředník nebude ochoten prověřovat, a je tedy na Vás, jakou cestu zvolíte.</p>
<h1>2. Registrace provozovny (RVPS)</h1>
<ul>
<li>Jako prodejce potravin jste <strong>provozovatel potravinářského podniku</strong> – zaregistrujte svou provozovnu u místně příslušné <strong>RVPS</strong> (§ 6 zák. 152/1995 Z. z., čl. 6 nař. 852/2004). Platí i pro stánkový/tržní prodej a e-shop; <strong>každá forma prodeje je samostatná provozovna</strong>.</li>
<li><strong>Stačí registrace</strong> (ne schvalovací číslo) – schválení se vyžaduje jen při výrobě/manipulaci s produkty živočišného původu dodávanými dalším provozovnám. Pro prodej baleného výrobku konečnému spotřebiteli stačí registrace.</li>
<li>Podává se elektronicky přes <strong>slovensko.sk</strong>, se správním poplatkem; RVPS vydá potvrzení. Prodávejte výrobky se <strong>slovenským označením</strong> a dodržujte správnou hygienickou praxi.</li>
<li><strong>Pozor – nejde o plný výrobní HACCP.</strong> U prodeje pouze balených, skladově stálých výrobků stačí zjednodušené postupy dle zásad HACCP (čistý sklad, ochrana před škůdci, skladování dle obalu, sledovatelnost dodavatele/šarže) – jednoduchá hygienická dokumentace na míru provozu, lze vytvořit i za pomoci AI.</li>
</ul>
<h1>3. Nahlášení zásilky od nás (do 24 hodin)</h1>
<ul>
<li>Při každé zásilce z ČR jste jako příjemce povinni <strong>do 24 hodin po převzetí</strong> nahlásit ji RVPS v systému <strong>Evidencia zásielok</strong> (§ 33 ods. 3 zák. 39/2007 Z. z.), komodita „Hmyz ako potravina".</li>
<li>Nahlašuje se: místo určení, země původu (ČR), druh a množství, druh obalu a identifikace výrobku. Podklady (dodací list, číslo šarže) dostanete od nás.</li>
</ul>
<h1>4. Registrační pokladna (eKasa)</h1>
<ul>
<li>Tržby evidujte v <strong>eKase</strong> – platí i pro prodej na trhu/stánku. Nejjednodušší je <strong>VRP (virtuálna registračná pokladnica)</strong> – bezplatná aplikace Finanční správy do mobilu; přenosná, bez nákupu zařízení.</li>
</ul>
<h1>Užitečné odkazy</h1>
<ul>
<li>Nahlášení zásilek (Evidencia zásielok): <a href="https://zasielky.svps.sk" target="_blank" rel="noopener">zasielky.svps.sk</a></li>
<li>Registrace provozovny (ŠVPS/RVPS): <a href="https://svps.sk/potraviny/registracia-prevadzkarni-potravinarskych-podnikov-pre-potraviny-zivocisneho-povodu/" target="_blank" rel="noopener">svps.sk – registrácia prevádzkarní</a></li>
<li>Informační systémy ŠVPS SR: <a href="https://svps.sk/informacne-systemy/" target="_blank" rel="noopener">svps.sk/informacne-systemy</a></li>
<li>eKasa / VRP (Finančná správa): <a href="https://www.financnasprava.sk" target="_blank" rel="noopener">financnasprava.sk</a></li>
</ul>
<p><em>Tento přehled je jen orientační a není úplným právním výkladem; předpisy se mohou měnit. Doporučujeme ověřit si aktuální povinnosti u příslušné RVPS, ŠVPS SR a Finanční správy SR, případně u odborného poradce.</em></p>
HTML;

        Stranka::firstOrCreate(['slug' => 'slovensky-trh'], [
            'nadpis' => 'Prodej sušeného jedlého hmyzu na Slovensku',
            'obsah' => $obsah,
        ]);
    }

    public function down(): void
    {
        Stranka::where('slug', 'slovensky-trh')->delete();
    }
};
