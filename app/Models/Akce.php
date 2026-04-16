<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Akce extends Model
{
    use HasFactory;

    protected $table = 'akce';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'nazev',
        'slug',
        'typ',
        'popis',
        'datum_od',
        'datum_do',
        'misto',
        'adresa',
        'gps_lat',
        'gps_lng',
        'okres',
        'kraj',
        'organizator',
        'kontakt_email',
        'kontakt_telefon',
        'web_url',
        'zdroj_url',
        'zdroj_typ',
        'najem',
        'obrat',
        'poznamka',
        'stav',
        'uzivatel_id',
    ];

    protected function casts(): array
    {
        return [
            'datum_od' => 'date',
            'datum_do' => 'date',
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'najem' => 'integer',
            'obrat' => 'integer',
        ];
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }

    public function rezervace(): HasMany
    {
        return $this->hasMany(Rezervace::class, 'akce_id');
    }

    public function zdroj(): BelongsTo
    {
        return $this->belongsTo(Zdroj::class, 'zdroj_id');
    }
}
