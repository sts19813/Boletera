<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class PhaseViewController extends Controller
{
    public function index()
    {
        // Cargar proyectos para el select
        $projects = Project::select('id', 'name')->get();
        return view('api.phases.index', compact('projects'));
    }
}
