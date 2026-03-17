<?php

namespace App\Http\Controllers;

class UnauthorizedController extends Controller
{
    public function index()
    {
        return view('unauthorized');
    }
}
