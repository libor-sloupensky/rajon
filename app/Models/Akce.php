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

    /** 7 krajů východní ČR (filtr pro scraping). */
    public const KRAJE_VYCHOD = [
        'Kraj Vysočina',
        'Královéhradecký kraj',
        'Pardubický kraj',
        'Olomoucký kraj',
        'Moravskoslezský kraj',
        'Zlínský kraj',
        'Jihomoravský kraj',
    ];

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
        'vstupne',
        'poznamka',
        'stav',
        'uzivatel_id',
        'zdroj_id',
        'externi_hash',
        'propojena_s_akci_id',
        'velikost_skore',
        'velikost_stav',
        'velikost_info',
        'velikost_signaly',
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
            'velikost_skore' => 'integer',
            'velikost_signaly' => 'array',
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

    public function propojenaSAkci(): BelongsTo
    {
        return $this->belongsTo(self::class, 'propojena_s_akci_id');
    }

    public function propojeneAkce(): HasMany
    {
        return $this->hasMany(self::class, 'propojena_s_akci_id');
    }

    public function akceZdroje(): HasMany
    {
        return $this->hasMany(AkceZdroj::class, 'akce_id');
    }

    /** Je kraj akce ve sledovaném regionu (východní ČR)? */
    public function jeVRegionu(): bool
    {
        return in_array($this->kraj, self::KRAJE_VYCHOD, true);
    }
}
