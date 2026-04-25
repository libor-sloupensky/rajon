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

        // Cost statistiky — dnešek, týden, měsíc + per uživatel
        $kurz = (float) config('scraping.kurz_usd_czk', 24);
        $statsZakladni = $this->statsAi();
        $statsPerUzivatel = $this->statsPerUzivatel();

        return view('admin.scraping.index', compact(
            'zdroje', 'posledniLogy', 'statsZakladni', 'statsPerUzivatel', 'kurz'
        ));
    }

    /** Stats AI volání: dnes / týden / měsíc / celkem. */
    protected function statsAi(): array
    {
        $base = \App\Models\AiVolani::query()->where('uspech', true);

        return [
            'dnes' => $this->statsRaw((clone $base)->whereDate('vytvoreno', today())),
            'tyden' => $this->statsRaw((clone $base)->where('vytvoreno', '>=', now()->subDays(7))),
            'mesic' => $this->statsRaw((clone $base)->where('vytvoreno', '>=', now()->subDays(30))),
            'celkem' => $this->statsRaw(clone $base),
        ];
    }

    protected function statsRaw($q): array
    {
        $row = (clone $q)->selectRaw('
            COUNT(*) as pocet,
            COALESCE(SUM(input_tokens + cache_creation_tokens + cache_read_tokens), 0) as input,
            COALESCE(SUM(output_tokens), 0) as output,
            COALESCE(SUM(cena_usd), 0) as cena
        ')->first();

        return [
            'pocet' => (int) ($row->pocet ?? 0),
            'tokens' => (int) ($row->input ?? 0) + (int) ($row->output ?? 0),
            'cena_usd' => (float) ($row->cena ?? 0),
        ];
    }

    protected function statsPerUzivatel(): array
    {
        return \App\Models\AiVolani::query()
            ->where('uspech', true)
            ->where('vytvoreno', '>=', now()->subDays(30))
            ->selectRaw('uzivatel_id, COUNT(*) as pocet, SUM(cena_usd) as cena')
            ->groupBy('uzivatel_id')
            ->orderByDesc('cena')
            ->take(10)
            ->with('uzivatel:id,jmeno,prijmeni')
            ->get()
            ->map(fn ($r) => [
                'jmeno' => $r->uzivatel?->celejmeno() ?? 'systém',
                'pocet' => (int) $r->pocet,
                'cena_usd' => (float) $r->cena,
            ])
            ->all();
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
        // Default: ukládat všechny kraje ČR. Filter zapne admin volitelně.
        $pouzeRegion = $request->boolean('pouze_vychod');

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
