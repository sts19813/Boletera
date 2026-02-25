<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Phase;

class PhaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Phase::with('project', 'stages.lots');

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return $query->get();
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string',
            'start_date' => 'nullable|date',
        ]);

        $phase = Phase::create($request->all());

        // ✅ Devolverlo con la relación cargada
        return response()->json(
            $phase->load('project'),
            201
        );
    }

    public function show(Phase $phase)
    {
        // ✅ También aquí debe incluir project
        return $phase->load('project', 'stages.lots');
    }

    public function update(Request $request, Phase $phase)
    {
        $phase->update($request->all());

        return response()->json(
            $phase->load('project')
        );
    }

    public function destroy(Phase $phase)
    {
        $phase->delete();
        return response()->json(null, 204);
    }
}
