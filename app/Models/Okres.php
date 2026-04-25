<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Okres extends Model
{
    protected $table = 'okresy';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'kraj_id',
        'nazev',
        'slug',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function kraj(): BelongsTo
    {
        return $this->belongsTo(Kraj::class, 'kraj_id');
    }

    public function akce(): HasMany
    {
        return $this->hasMany(Akce::class, 'okres_id');
    }
}
