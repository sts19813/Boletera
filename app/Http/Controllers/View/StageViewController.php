<?php

namespace App\Http\Controllers\View;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phase;
use App\Models\Project;
use App\Models\Enterprise;

class StageViewController extends Controller
{
    public function index()
    {
        $phases = Phase::select('id', 'name', 'project_id')->get();
        $projects = Project::select('id', 'name')->get();
        $enterprises = Enterprise::select('id', 'business_name')->get();

        return view('api.stages.index', compact('phases', 'enterprises', 'projects'));
    }
}
