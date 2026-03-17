<?php

namespace App\Http\Controllers;

use App\Models\TicketCheckin;
use App\Models\TicketInstance;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    /**
     * Vista del escaner
     */
    public function index()
    {
        return view('checkin.scanner');
    }

    /**
     * Validar boleto
     */
    public function validateTicket(Request $request)
    {
        $request->validate([
            'ticket_instance_id' => 'nullable|uuid|required_without:registration_instance_id',
            'registration_instance_id' => 'nullable|uuid|required_without:ticket_instance_id',
            'hash' => 'required|string',
        ]);

        $instanceId = $request->ticket_instance_id ?? $request->registration_instance_id;

        $ticket = TicketInstance::with('ticket')
            ->where('id', $instanceId)
            ->where('qr_hash', $request->hash)
            ->first();

        if (!$ticket) {
            TicketCheckin::create([
                'ticket_instance_id' => $instanceId,
                'hash' => $request->hash,
                'result' => 'invalid',
                'message' => 'Boleto invalido',
                'scanned_at' => now(),
                'scanner_ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Boleto invalido',
            ], 404);
        }

        // Registros (sin ticket asociado) usan 1 check-in por defecto.
        $maxCheckins = $ticket->ticket?->max_checkins ?? 1;

        $usedCount = TicketCheckin::where('ticket_instance_id', $ticket->id)
            ->where('result', 'success')
            ->count();

        if ($usedCount >= $maxCheckins) {
            TicketCheckin::create([
                'ticket_instance_id' => $ticket->id,
                'hash' => $request->hash,
                'result' => 'used',
                'message' => 'Cupo agotado',
                'scanned_at' => now(),
                'scanner_ip' => $request->ip(),
            ]);

            $history = TicketCheckin::where('ticket_instance_id', $ticket->id)
                ->where('result', 'success')
                ->orderBy('scanned_at')
                ->get()
                ->map(fn($h, $i) => [
                    'numero' => ($i + 1) . '/' . $maxCheckins,
                    'hora' => $h->scanned_at->format('d/m/Y H:i:s'),
                ]);

            return response()->json([
                'status' => 'used',
                'message' => 'Este boleto ya alcanzo su limite',
                'history' => $history,
            ]);
        }

        if (!$ticket->used_at) {
            $ticket->update([
                'used_at' => now(),
            ]);
        }

        TicketCheckin::create([
            'ticket_instance_id' => $ticket->id,
            'hash' => $request->hash,
            'result' => 'success',
            'message' => 'Acceso permitido',
            'scanned_at' => now(),
            'scanner_ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Acceso permitido',
            'email' => $ticket->email,
            'progress' => ($usedCount + 1) . '/' . $maxCheckins,
            'used_at' => $ticket->used_at->format('d/m/Y H:i:s'),
        ]);
    }

    public function stats()
    {
        $total = TicketInstance::count();

        $scanned = TicketCheckin::where('result', 'success')
            ->distinct('ticket_instance_id')
            ->count('ticket_instance_id');

        $courtesyScanned = TicketInstance::where('email', 'CORTESIA')
            ->whereHas('checkins', function ($q) {
                $q->where('result', 'success');
            })
            ->count();

        $pending = $total - $scanned;

        return view('checkin.stats', compact(
            'total',
            'scanned',
            'courtesyScanned',
            'pending'
        ));
    }
}
