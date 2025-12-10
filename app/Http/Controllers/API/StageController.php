<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stage;

class StageController extends Controller
{
    public function index(Request $request)
    {
        $query = Stage::with(['phase', 'phase.project', 'enterprise', 'lots', 'customFields']);

        if ($request->has('phase_id') && $request->phase_id) {
            $query->where('phase_id', $request->phase_id);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'phase_id' => 'required|exists:phases,id',
            'name' => 'required|string',
            'credit_scheme_id' => 'nullable|integer',
            'enterprise_id' => 'nullable|exists:enterprises,id',
        ]);

        $stage = Stage::create($request->all());

        if ($request->has('custom_fields')) {
            foreach ($request->custom_fields as $cf) {
                $stage->customFields()->create($cf);
            }
        }

        return response()->json(
            $stage->load([
                'phase.project', 
                'enterprise',    
                'customFields'
            ]),
            201
        );
    }

    public function show(Stage $stage)
    {
        return $stage->load([
            'phase.project', 
            'enterprise',       
            'lots', 
            'customFields'
        ]);
    }

    public function update(Request $request, Stage $stage)
    {
        $stage->update($request->all());

        if ($request->has('custom_fields')) {
            foreach ($request->custom_fields as $cf) {
                $stage->customFields()->updateOrCreate(
                    ['code' => $cf['code']],
                    ['value' => $cf['value']]
                );
            }
        }

        return response()->json(
            $stage->load([
                'phase.project', 
                'enterprise',
                'customFields'
            ])
        );
    }

    public function destroy(Stage $stage)
    {
        $stage->delete();
        return response()->json(null, 204);
    }
}
