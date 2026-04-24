<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScrapingLog;
use App\Models\Zdroj;
use App\Services\Scraping\AkceExtractor;
use App\Services\Scraping\ScrapingPipeline;
use App\Services\Scraping\ZdrojAnalyzer;
use Illuminate\Http\Request;

class ScrapingController extends Controller
{
    public function __construct(
        protected ZdrojAnalyzer $analyzer,
        protected AkceExtractor $extractor,
        protected ScrapingPipeline $pipeline,
    ) {}

    /** Seznam zdrojů + statistiky. */
    public function index()
    {
        $zdroje = Zdroj::withCount('akce')->orderBy('nazev')->get();
        $posledniLogy = ScrapingLog::with('zdroj')->orderBy('zacatek', 'desc')->take(10)->get();

        return view('admin.scraping.index', compact('zdroje', 'posledniLogy'));
    }

    /** Formulář pro přidání/úpravu zdroje. */
    public function create()
    {
        return view('admin.scraping.create');
    }

    public function edit(Zdroj $zdroj)
    {
        return view('admin.scraping.edit', compact('zdroj'));
    }

    /** AI analýza URL (AJAX) — vrátí strukturu + návrh zdroje. */
    public function analyzovat(Request $request)
    {
        $request->validate(['url' => ['required', 'url', 'max:500']]);

        $analyza = $this->analyzer->analyzuj($request->url);

        // Pokud je sitemap, spočítej kolik URL
        $pocetUrlVSitemap = 0;
        if (!empty($analyza['sitemap_url'])) {
            $urls = $this->analyzer->seznamUrlZSitemap($analyza['sitemap_url'], '*');
            $pocetUrlVSitemap = count($urls);
        }

        return response()->json([
            'ok' => true,
            'analyza' => $analyza,
            'pocet_url_v_sitemap' => $pocetUrlVSitemap,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nazev' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'typ' => ['required', 'string'],
            'cms_typ' => ['nullable', 'string', 'max:50'],
            'sitemap_url' => ['nullable', 'url', 'max:500'],
            'robots_url' => ['nullable', 'url', 'max:500'],
            'url_pattern_detail' => ['nullable', 'string', 'max:200'],
            'frekvence_hodin' => ['required', 'integer', 'min:1'],
            'poznamka' => ['nullable', 'string'],
        ]);

        $data['stav'] = 'aktivni';
        $data['uzivatel_id'] = $request->user()->id;

        Zdroj::create($data);

        return redirect()->route('admin.scraping.index')->with('success', 'Zdroj přidán.');
    }

    public function update(Request $request, Zdroj $zdroj)
    {
        $data = $request->validate([
            'nazev' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'typ' => ['required', 'string'],
            'cms_typ' => ['nullable', 'string', 'max:50'],
            'sitemap_url' => ['nullable', 'url', 'max:500'],
            'url_pattern_detail' => ['nullable', 'string', 'max:200'],
            'frekvence_hodin' => ['required', 'integer', 'min:1'],
            'stav' => ['required', 'string'],
            'poznamka' => ['nullable', 'string'],
        ]);

        $zdroj->update($data);

        return redirect()->route('admin.scraping.index')->with('success', 'Zdroj aktualizován.');
    }

    /** Spustí scraping (synchronně pro test, později přes queue). */
    public function spustit(Request $request, Zdroj $zdroj)
    {
        $limit = (int) $request->input('limit', 10);
        $pouzeRegion = !$request->has('ignore_region');

        $log = $this->pipeline->scrapujZdroj($zdroj, $limit, $pouzeRegion);

        return redirect()->route('admin.scraping.log', $log)
            ->with('success', "Scraping dokončen: {$log->pocet_novych} nových, {$log->pocet_aktualizovanych} aktualizovaných.");
    }

    public function log(ScrapingLog $log)
    {
        $log->load('zdroj');
        return view('admin.scraping.log', compact('log'));
    }
}
