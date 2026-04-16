<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AkceScraperService
{
    /**
     * Stáhne HTML stránky a vrátí ho k dalšímu zpracování.
     */
    public function stahniHtml(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; Rajon/1.0)',
                    'Accept-Language' => 'cs,en;q=0.5',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            \Log::warning("Scraping failed for {$url}: {$e->getMessage()}");
        }

        return null;
    }
}
