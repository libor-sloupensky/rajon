<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Navsteva extends Model
{
    protected $table = 'navstevy';
    public $timestamps = false;

    protected $fillable = ['uzivatel_id', 'zacatek', 'konec'];

    protected function casts(): array
    {
        return [
            'zacatek' => 'datetime',
            'konec' => 'datetime',
        ];
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }
}
