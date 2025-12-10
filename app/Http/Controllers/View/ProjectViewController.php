<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class ProjectViewController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name')->get();
        return view('api.projects.index', compact('users'));
    }
}
