<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdaraService;

class AdaraController extends Controller
{
    public function __construct(private AdaraService $adara) {}

    public function projects()
    {
        return response()->json($this->adara->getProjects());
    }

    public function phases($id)
    {
        return response()->json($this->adara->getPhases($id));
    }

    public function stages($project, $phase)
    {
        return response()->json($this->adara->getStages($project, $phase));
    }

    public function lots($project, $phase, $stage)
    {
        return response()->json($this->adara->getLots($project, $phase, $stage));
    }
}
