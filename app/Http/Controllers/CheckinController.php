<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketInstance;
use App\Models\TicketCheckin;
class CheckinController extends Controller
{
	/**
	 * Vista del escáner
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
			'ticket_instance_id' => 'required|uuid',
			'hash' => 'required|string',
		]);

		$ticket = TicketInstance::where('id', $request->ticket_instance_id)
			->where('qr_hash', $request->hash)
			->first();

		// ❌ BOLETO INVÁLIDO
		if (!$ticket) {
			TicketCheckin::create([
				'ticket_instance_id' => $request->ticket_instance_id,
				'hash' => $request->hash,
				'result' => 'invalid',
				'message' => 'Boleto inválido',
				'scanned_at' => now(),
				'scanner_ip' => $request->ip(),
			]);

			return response()->json([
				'status' => 'error',
				'message' => 'Boleto inválido'
			], 404);
		}

		// ⚠️ YA USADO
		if ($ticket->used_at) {

			TicketCheckin::create([
				'ticket_instance_id' => $ticket->id,
				'hash' => $request->hash,
				'result' => 'used',
				'message' => 'Boleto ya utilizado',
				'scanned_at' => now(),
				'scanner_ip' => $request->ip(),
			]);

			$history = TicketCheckin::where('ticket_instance_id', $ticket->id)
				->orderBy('scanned_at', 'desc')
				->get()
				->map(fn($h) => [
					'hora' => $h->scanned_at->format('d/m/Y H:i:s'),
					'resultado' => $h->result,
				]);

			return response()->json([
				'status' => 'used',
				'message' => 'Este boleto ya fue utilizado',
				'used_at' => $ticket->used_at->format('d/m/Y H:i:s'),
				'history' => $history
			]);
		}

		// ✅ PRIMER USO
		$ticket->update([
			'used_at' => now()
		]);

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
			'used_at' => $ticket->used_at->format('d/m/Y H:i:s'),
		]);
	}
}
