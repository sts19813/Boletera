<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AdminController extends Controller
{
    public function index()
    {
        return Auth::check()
            ? redirect()->route('events.index')
            : view('login');
    }
}
