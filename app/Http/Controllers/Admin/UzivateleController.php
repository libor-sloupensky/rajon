<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Uzivatel;
use Illuminate\Http\Request;

class UzivateleController extends Controller
{
    public function index()
    {
        $uzivatele = Uzivatel::orderBy('vytvoreno', 'desc')->paginate(20);

        return view('admin.uzivatele.index', compact('uzivatele'));
    }
}
