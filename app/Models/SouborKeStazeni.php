<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SouborKeStazeni extends Model
{
    protected $table = 'soubory_ke_stazeni';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = [
        'nazev', 'popis', 'zdroj', 'cesta', 'download_nazev', 'typ', 'velikost', 'poradi',
    ];

    /** URL ke stažení — public soubory přímo, storage přes controller. */
    public function urlKeStazeni(): string
    {
        return $this->zdroj === 'public'
            ? asset('soubory/' . $this->cesta)
            : route('informace.soubor.stahnout', $this);
    }

    /** Velikost čitelně (kB / MB). */
    public function velikostText(): string
    {
        $b = (int) $this->velikost;
        if ($b <= 0) return '';
        if ($b >= 1048576) return number_format($b / 1048576, 1, ',', ' ') . ' MB';
        return number_format($b / 1024, 0, ',', ' ') . ' kB';
    }
}
