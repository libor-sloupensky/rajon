<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AkceAiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model = config('services.anthropic.model');
    }

    /**
     * Extrahuje akce z HTML stránky pomocí AI.
     */
    public function extrahujAkce(string $html, string $url): array
    {
        $html = mb_substr(strip_tags($html), 0, 8000);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 4096,
            'system' => 'Jsi analytik specializovaný na extrakci informací o akcích (pouť, festival, slavnosti, trhy, vinobraní atd.) z českých webových stránek. Odpovídej POUZE platným JSON polem.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Extrahuj všechny akce z následujícího textu. Pro každou akci vrať objekt s poli: nazev, typ (pout/food_festival/slavnosti/vinobrani/dynobrani/farmarske_trhy/vanocni_trhy/jarmark/festival/jiny), datum_od (YYYY-MM-DD), datum_do (YYYY-MM-DD), misto, adresa, organizator, kontakt_email, kontakt_telefon, web_url, popis.\n\nZdroj URL: {$url}\n\nText:\n{$html}",
                ],
            ],
        ]);

        if (!$response->successful()) {
            Log::error('AI extraction failed', ['status' => $response->status(), 'url' => $url]);
            return [];
        }

        $content = $response->json('content.0.text', '');

        // Extrahuj JSON z odpovědi
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            $akce = json_decode($matches[0], true);
            return is_array($akce) ? $akce : [];
        }

        return [];
    }

    /**
     * Zpracuje e-mail a extrahuje informace o akci.
     */
    public function zpracujEmail(string $predmet, string $telo): ?array
    {
        $telo = mb_substr(strip_tags($telo), 0, 4000);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => 2048,
            'system' => 'Jsi analytik. Z e-mailu extrahuj informace o akci (festivalu, pouti, slavnostech). Odpověz POUZE platným JSON objektem.',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Předmět: {$predmet}\n\nTělo:\n{$telo}\n\nExtrahuj: nazev, typ, datum_od (YYYY-MM-DD), datum_do, misto, adresa, organizator, kontakt_email, kontakt_telefon, web_url, najem (CZK), popis.",
                ],
            ],
        ]);

        if (!$response->successful()) {
            return null;
        }

        $content = $response->json('content.0.text', '');
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            return json_decode($matches[0], true);
        }

        return null;
    }
}
