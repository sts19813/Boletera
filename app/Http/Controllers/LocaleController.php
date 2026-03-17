<?php

namespace App\Http\Controllers;

class LocaleController extends Controller
{
    public function switch(string $lang)
    {
        session(['locale' => $lang]);
        return back();
    }
}
