<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rezervace extends Model
{
    use HasFactory;

    protected $table = 'rezervace';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'akce_id',
        'uzivatel_id',
        'stav',
        'poznamka',
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
