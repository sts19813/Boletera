<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketInstance;

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

		if (!$ticket) {
			return response()->json([
				'status' => 'error',
				'message' => 'Boleto inválido'
			], 404);
		}

		if ($ticket->used_at) {
			return response()->json([
				'status' => 'used',
				'message' => 'Este boleto ya fue utilizado',
				'used_at' => $ticket->used_at->format('d/m/Y H:i')
			]);
		}

		$ticket->update([
			'used_at' => now()
		]);

		return response()->json([
			'status' => 'success',
			'message' => 'Acceso permitido',
			'email' => $ticket->email
		]);
	}
}
