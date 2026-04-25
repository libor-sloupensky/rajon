<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkceZdroj extends Model
{
    use HasFactory;

    protected $table = 'akce_zdroje';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'akce_id',
        'zdroj_id',
        'url',
        'externi_id',
        'je_od_poradatele',
        'surova_data',
        'html_hash',
        'lastmod_sitemap',
        'posledni_kontrola',
        'posledni_extrakce',
        'pocet_extrakci',
        'posledni_ziskani',
    ];

    protected function casts(): array
    {
        return [
            'surova_data' => 'array',
            'posledni_ziskani' => 'datetime',
            'lastmod_sitemap' => 'datetime',
            'posledni_kontrola' => 'datetime',
            'posledni_extrakce' => 'datetime',
            'je_od_poradatele' => 'boolean',
        ];
    }

    public function akce(): BelongsTo
    {
        return $this->belongsTo(Akce::class, 'akce_id');
    }

    public function zdroj(): BelongsTo
    {
        return $this->belongsTo(Zdroj::class, 'zdroj_id');
    }
}
