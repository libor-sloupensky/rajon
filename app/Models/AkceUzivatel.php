<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user data k akci — osobní poznámka + palec hodnocení (nahoru/stred/dolu).
 * Každý uživatel má svoji vlastní instanci, ostatní ji nevidí.
 */
class AkceUzivatel extends Model
{
    protected $table = 'akce_uzivatel';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'akce_id',
        'uzivatel_id',
        'palec',
        'osobni_poznamka',
    ];

    public function akce(): BelongsTo
    {
        return $this->belongsTo(Akce::class, 'akce_id');
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }
}
