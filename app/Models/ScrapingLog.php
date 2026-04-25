<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapingLog extends Model
{
    use HasFactory;

    protected $table = 'scraping_log';
    public $timestamps = false;

    protected $fillable = [
        'zdroj_id',
        'zacatek',
        'konec',
        'stav',
        'limit_pouzity',
        'pocet_nalezenych',
        'pocet_novych',
        'pocet_aktualizovanych',
        'pocet_preskocenych',
        'pocet_chyb',
        'chyby_detail',
        'statistiky',
        'vytvoreno',
    ];

    /** Skutečně zpracovaných URL (součet všech stavů). */
    public function getPocetZpracovanychAttribute(): int
    {
        return (int) ($this->pocet_novych
            + $this->pocet_aktualizovanych
            + $this->pocet_preskocenych
            + $this->pocet_chyb);
    }

    protected function casts(): array
    {
        return [
            'zacatek' => 'datetime',
            'konec' => 'datetime',
            'vytvoreno' => 'datetime',
            'statistiky' => 'array',
        ];
    }

    public function zdroj(): BelongsTo
    {
        return $this->belongsTo(Zdroj::class, 'zdroj_id');
    }
}
