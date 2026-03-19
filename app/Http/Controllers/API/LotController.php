<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['event']);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->event_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|uuid|exists:eventos,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'total_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'sold' => 'nullable|integer|min:0',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'description' => 'nullable|string',
            'is_courtesy' => 'nullable|boolean',
            'status' => 'nullable|string|max:50',
            'max_checkins' => 'nullable|integer|min:1',
        ]);

        $ticket = Ticket::create($validated);

        return response()->json(
            $ticket->load(['event', 'customFields']),
            201
        );
    }

    public function show(Ticket $ticket)
    {
        return $ticket->load(['event', 'customFields']);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'event_id' => 'sometimes|required|uuid|exists:eventos,id',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|max:255',
            'total_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'sold' => 'nullable|integer|min:0',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'description' => 'nullable|string',
            'is_courtesy' => 'nullable|boolean',
            'status' => 'nullable|string|max:50',
            'max_checkins' => 'nullable|integer|min:1',
        ]);

        $ticket->update($validated);

        return response()->json(
            $ticket->load(['event', 'customFields'])
        );
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return response()->json(null, 204);
    }
}
