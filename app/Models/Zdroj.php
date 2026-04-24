<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zdroj extends Model
{
    use HasFactory;

    protected $table = 'zdroje';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'nazev',
        'url',
        'robots_url',
        'sitemap_url',
        'cms_typ',
        'url_pattern_list',
        'url_pattern_detail',
        'struktura',
        'frekvence_hodin',
        'posledni_chyby',
        'vyzaduje_login',
        'je_web_poradatele',
        'typ',
        'stav',
        'posledni_scraping',
        'pocet_akci',
        'uzivatel_id',
        'poznamka',
    ];

    protected function casts(): array
    {
        return [
            'posledni_scraping' => 'datetime',
            'pocet_akci' => 'integer',
            'frekvence_hodin' => 'integer',
            'struktura' => 'array',
            'vyzaduje_login' => 'boolean',
            'je_web_poradatele' => 'boolean',
        ];
    }

    public function akce(): HasMany
    {
        return $this->hasMany(Akce::class, 'zdroj_id');
    }

    public function akceZdroje(): HasMany
    {
        return $this->hasMany(AkceZdroj::class, 'zdroj_id');
    }

    public function scrapingLog(): HasMany
    {
        return $this->hasMany(ScrapingLog::class, 'zdroj_id');
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }
}
