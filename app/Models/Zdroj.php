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
        ];
    }

    public function akce(): HasMany
    {
        return $this->hasMany(Akce::class, 'zdroj_id');
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }
}
