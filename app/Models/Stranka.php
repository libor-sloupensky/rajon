<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Editovatelná informační stránka (článek). Obsah je HTML, edituje se na webu
 * přes WYSIWYG (Trix). Strukturované stránky (FAQ, Vybavení) sem nepatří.
 */
class Stranka extends Model
{
    protected $table = 'stranky';

    const CREATED_AT = 'vytvoreno';
    const UPDATED_AT = 'upraveno';

    protected $fillable = ['slug', 'nadpis', 'obsah'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
