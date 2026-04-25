<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVolani extends Model
{
    protected $table = 'ai_volani';
    public $timestamps = false;

    protected $fillable = [
        'model',
        'ucel',
        'zdroj_id',
        'akce_id',
        'uzivatel_id',
        'scraping_log_id',
        'input_tokens',
        'output_tokens',
        'cache_creation_tokens',
        'cache_read_tokens',
        'cena_usd',
        'uspech',
        'chyba',
        'vytvoreno',
    ];

    protected function casts(): array
    {
        return [
            'cena_usd' => 'decimal:6',
            'uspech' => 'boolean',
            'vytvoreno' => 'datetime',
        ];
    }

    public function uzivatel(): BelongsTo
    {
        return $this->belongsTo(Uzivatel::class, 'uzivatel_id');
    }

    public function zdroj(): BelongsTo
    {
        return $this->belongsTo(Zdroj::class, 'zdroj_id');
    }
}
