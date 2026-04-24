<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Pozvanka extends Model
{
    use HasFactory;

    protected $table = 'pozvanky';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'token',
        'email',
        'jmeno',
        'prijmeni',
        'role',
        'region',
        'stav',
        'plati_do',
        'prijata_v',
        'pozval_uzivatel_id',
        'uzivatel_id',
        'poznamka',
    ];

    protected function casts(): array
    {
        return [
            'plati_do' => 'datetime',
            'prijata_v' => 'datetime',
        ];
    }

    public function pozval(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'pozval_uzivatel_id');
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }

    /** Je pozvánka platná? (nepřijatá, neexpirovaná, nezrušená) */
    public function jePlatna(): bool
    {
        if ($this->stav !== 'cekajici') return false;
        if ($this->plati_do && $this->plati_do->isPast()) return false;
        return true;
    }

    /** Generuj náhodný unikátní token. */
    public static function generujToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());
        return $token;
    }

    /** URL pro přijetí pozvánky. */
    public function url(): string
    {
        return url('/registrace?token=' . $this->token);
    }
}
