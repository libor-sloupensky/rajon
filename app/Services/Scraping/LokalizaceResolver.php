<?php

namespace App\Services\Scraping;

use App\Models\Kraj;
use App\Models\Okres;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Převede AI extrahované textové názvy kraje/okresu na ID z DB.
 * Také vrací seznam okresů pro AI prompt (formátovaný jako enum).
 */
class LokalizaceResolver
{
    /**
     * Najdi kraj_id podle textu (case-insensitive, slug match).
     * Vrátí null pokud žádný odpovídající kraj.
     */
    public function najdiKrajId(?string $nazevNeboSlug): ?int
    {
        if (empty($nazevNeboSlug)) return null;
        $slug = Str::slug($nazevNeboSlug);
        return $this->mapaKrajeSlugId()[$slug] ?? null;
    }

    /**
     * Najdi okres_id podle textu (case-insensitive, slug match).
     * Vrátí null pokud žádný odpovídající okres.
     */
    public function najdiOkresId(?string $nazevNeboSlug): ?int
    {
        if (empty($nazevNeboSlug)) return null;
        $slug = Str::slug($nazevNeboSlug);
        return $this->mapaOkresySlugId()[$slug] ?? null;
    }

    /**
     * Pokud je vyplněn okres, najdi i jeho kraj. Vrací [kraj_id, okres_id].
     * Preferuj okres (přesnější), kraj se odvodí z něj.
     */
    public function resolve(?string $kraj, ?string $okres): array
    {
        $okresId = $this->najdiOkresId($okres);

        if ($okresId) {
            $okresModel = Okres::find($okresId);
            return [
                'kraj_id' => $okresModel?->kraj_id,
                'okres_id' => $okresId,
            ];
        }

        return [
            'kraj_id' => $this->najdiKrajId($kraj),
            'okres_id' => null,
        ];
    }

    /**
     * Vygeneruj seznam okresů pro AI prompt — pro každý kraj jeden řádek.
     * Cache 1 hodinu (data se nemění).
     */
    public function seznamProPrompt(): string
    {
        return Cache::remember('lokalizace.seznam_pro_prompt', 3600, function () {
            $vystup = [];
            $kraje = Kraj::with(['okresy' => fn ($q) => $q->orderBy('nazev')])
                ->orderBy('nazev')
                ->get();

            foreach ($kraje as $kraj) {
                $okresy = $kraj->okresy->pluck('nazev')->all();
                $vystup[] = "  - **{$kraj->nazev}** (okresy: " . implode(', ', $okresy) . ")";
            }

            return implode("\n", $vystup);
        });
    }

    public function vycistitCache(): void
    {
        Cache::forget('lokalizace.seznam_pro_prompt');
        Cache::forget('lokalizace.kraje_slug_id');
        Cache::forget('lokalizace.okresy_slug_id');
    }

    /** @return array<string,int> slug → id */
    protected function mapaKrajeSlugId(): array
    {
        return Cache::remember('lokalizace.kraje_slug_id', 3600,
            fn () => Kraj::pluck('id', 'slug')->all());
    }

    /** @return array<string,int> slug → id */
    protected function mapaOkresySlugId(): array
    {
        return Cache::remember('lokalizace.okresy_slug_id', 3600,
            fn () => Okres::pluck('id', 'slug')->all());
    }
}
