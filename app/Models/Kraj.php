<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kraj extends Model
{
    protected $table = 'kraje';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'nazev',
        'slug',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function okresy(): HasMany
    {
        return $this->hasMany(Okres::class, 'kraj_id');
    }

    public function akce(): HasMany
    {
        return $this->hasMany(Akce::class, 'kraj_id');
    }
}
