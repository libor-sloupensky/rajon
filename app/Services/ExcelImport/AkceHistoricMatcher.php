<?php

namespace App\Services\ExcelImport;

use App\Models\Akce;

/**
 * Najdi existující kanonickou akci (bez termínu) pro data z Excelu.
 *
 * Na rozdíl od scraping `AkceMatcher` neporovnává datum — kanonická akce
 * v modulu historicka_data reprezentuje akci napříč ročníky. Matcher hledá:
 *   1) přesný slug podle normalizovaného názvu + místa
 *   2) fuzzy podle normalizovaného názvu (`similar_text`) + totožnost místa
 *
 * Používá in-memory cache — během jednoho importu se znova nečte DB.
 */
class AkceHistoricMatcher
{
    private int $similarityThreshold;

    /** @var array<string, Akce>  klíč = "{nazevNorm}|{mistoNorm}" */
    private array $cache = [];
    private bool $cacheNaplneno = false;

    public function __construct(private readonly bool $preskocDbNacten = false)
    {
        $this->similarityThreshold = (int) config('scraping.matching.similarity_threshold', 80);
    }

    /**
     * Najdi existující akci podle normalizovaného názvu + místa.
     */
    public function najdi(string $nazev, ?string $misto): ?Akce
    {
        $this->naplnCache();

        $nazevNorm = NazevNormalizer::normalizuj($nazev);
        $mistoNorm = $misto ? $this->normMisto($misto) : '';

        // 1) exact match klíče
        $klic = $nazevNorm . '|' . $mistoNorm;
        if (isset($this->cache[$klic])) {
            return $this->cache[$klic];
        }

        // 2) fuzzy — iterace přes cache. Porovnávají se dvě varianty každého
        // názvu: (a) čistý název (b) název + místo slepené dohromady. Match
        // projde, pokud aspoň jedna kombinace překročí threshold a místa
        // (pokud jsou obě) neodporují si.
        //
        // Tím se „Farmářské trhy Doudleby" (misto prázdné) namatchne na
        // „Farmářské trhy" + misto „Doudleby", ale „Farmářské trhy" (bez
        // místa) nezmění nic — protože sama sobě jen odpovídá.
        $nazevPlusMisto = trim($nazevNorm . '-' . $mistoNorm, '-');

        $best = null;
        $bestScore = 0.0;
        foreach ($this->cache as $cacheKlic => $akce) {
            [$cnazev, $cmisto] = explode('|', $cacheKlic, 2);

            // Obě strany mají místo → musí se shodovat (nebo substring).
            if ($mistoNorm !== '' && $cmisto !== ''
                && $mistoNorm !== $cmisto
                && !str_contains($mistoNorm, $cmisto)
                && !str_contains($cmisto, $mistoNorm)) {
                continue;
            }

            $cnazevPlus = trim($cnazev . '-' . $cmisto, '-');

            // Spočti similarity na 4 kombinacích, vem nejvyšší
            $bestPair = 0.0;
            foreach ([
                [$nazevNorm, $cnazev],
                [$nazevPlusMisto, $cnazevPlus],
                [$nazevPlusMisto, $cnazev],
                [$nazevNorm, $cnazevPlus],
            ] as [$a, $b]) {
                if ($a === '' || $b === '') continue;
                $sim = 0.0;
                similar_text($a, $b, $sim);
                if ($sim > $bestPair) $bestPair = $sim;
            }

            if ($bestPair < $this->similarityThreshold) continue;

            if ($bestPair > $bestScore) {
                $bestScore = $bestPair;
                $best = $akce;
            }
        }

        return $best;
    }

    /**
     * Přidá novou akci do cache (po vytvoření), aby ji další řádek našel.
     */
    public function pridej(Akce $akce): void
    {
        $nazevNorm = NazevNormalizer::normalizuj($akce->nazev);
        $mistoNorm = $akce->misto ? $this->normMisto($akce->misto) : '';
        $this->cache[$nazevNorm . '|' . $mistoNorm] = $akce;
    }

    private function naplnCache(): void
    {
        if ($this->cacheNaplneno) return;
        $this->cacheNaplneno = true;

        if ($this->preskocDbNacten) {
            return; // dry-run bez DB — cache se plní jen z pridej()
        }

        foreach (Akce::query()->get(['id', 'nazev', 'misto']) as $akce) {
            /** @var Akce $akce */
            $nazevNorm = NazevNormalizer::normalizuj($akce->nazev);
            $mistoNorm = $akce->misto ? $this->normMisto($akce->misto) : '';
            $this->cache[$nazevNorm . '|' . $mistoNorm] = $akce;
        }
        $this->cacheNaplneno = true;
    }

    private function normMisto(string $misto): string
    {
        return NazevNormalizer::normalizuj($misto);
    }
}
