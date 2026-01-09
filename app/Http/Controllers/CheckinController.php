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

		$ticket = TicketInstance::with('ticket')
			->where('id', $request->ticket_instance_id)
			->where('qr_hash', $request->hash)
			->first();

		/**
		 * ❌ BOLETO INVÁLIDO
		 */
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

		/**
		 * Datos del boleto
		 */
		$maxCheckins = $ticket->ticket->max_checkins ?? 1;

		$usedCount = TicketCheckin::where('ticket_instance_id', $ticket->id)
			->where('result', 'success')
			->count();

		/**
		 * ⚠️ CUPO AGOTADO
		 */
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
				'message' => 'Este boleto ya alcanzó su límite',
				'history' => $history
			]);
		}

		/**
		 * ✅ ACCESO PERMITIDO
		 */
		if (!$ticket->used_at) {
			$ticket->update([
				'used_at' => now() // primer acceso
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

}
