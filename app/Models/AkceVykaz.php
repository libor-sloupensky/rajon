<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AkceVykaz extends Model
{
    protected $table = 'akce_vykazy';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'akce_id',
        'rok',
        'datum_od',
        'datum_do',
        'trzba',
        'najem',
        'poznamka',
        'zdroj_excel',
    ];

    protected function casts(): array
    {
        return [
            'rok' => 'integer',
            'datum_od' => 'date',
            'datum_do' => 'date',
            'trzba' => 'integer',
            'najem' => 'integer',
        ];
    }

    public function akce(): BelongsTo
    {
        return $this->belongsTo(Akce::class, 'akce_id');
    }
}
