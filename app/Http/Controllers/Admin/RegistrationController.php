<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistrationInstance;
class RegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $instances = RegistrationInstance::with([
            'evento',
            'registration.players'
        ])->latest()->get();

        return view('admin.registrations.index', compact('instances'));
    }

    public function export($eventId)
    {
        $instances = RegistrationInstance::with([
            'evento',
            'registration.players'
        ])
            ->where('event_id', $eventId)
            ->whereNotNull('payment_intent_id')
            ->distinct('payment_intent_id')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=inscripciones.csv',
        ];

        $callback = function () use ($instances, $eventId) {

            $clean = function ($value) {
                if (is_array($value)) {
                    return implode(', ', array_map(fn($v) => strip_tags((string) $v), $value));
                }
                return is_string($value) ? strip_tags($value) : $value;
            };

            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            /*
            =====================================================
            EVENTO CENA GALA (header diferente)
            =====================================================
            */
            if ($eventId === '019c91a4-9f3b-7039-93cc-83f50c44c835') {

                fputcsv($file, [
                    'Tipo',
                    'Evento',
                    'Email Registro',
                    'Fecha Registro',
                    'Nombre',
                    'Email',
                    'Celular',
                    'Tipo Invitado',
                    'Generación',
                    'Subtotal',
                    'Total',
                ]);

            } else {

                /*
                =====================================================
                EVENTO GOLF (header original)
                =====================================================
                */
                fputcsv($file, [
                    'Tipo',
                    'Evento',
                    'Equipo',
                    'Email Registro',
                    'Fecha Registro',

                    'Jugador',
                    'Email Jugador',
                    'Celular',
                    'Campo',
                    'Handicap',
                    'GHIN',
                    'Talla',
                    'Capitán',
                    'Relación Cumbres',

                    'Subtotal',
                    'Total',
                ]);
            }

            $processedPayments = [];

            foreach ($instances as $instance) {

                if (in_array($instance->payment_intent_id, $processedPayments)) {
                    continue;
                }

                $processedPayments[] = $instance->payment_intent_id;

                $registration = $instance->registration;
                if (!$registration)
                    continue;

                $evento = $clean($instance->evento?->name ?? '—');
                $fecha = optional($instance->registered_at)->format('d/m/Y H:i');

                /*
                =====================================================
                CENA GALA
                =====================================================
                */
                if ($eventId === '019c91a4-9f3b-7039-93cc-83f50c44c835') {

                    if (!$registration->form_data)
                        continue;

                    $data = $registration->form_data;
                    if (!isset($data['participants']))
                        continue;

                    fputcsv($file, [
                        'INSCRIPCIÓN',
                        $evento,
                        $clean($instance->email),
                        $fecha,
                        '',
                        '',
                        '',
                        '',
                        '',
                        $registration->subtotal,
                        $registration->total,
                    ]);

                    foreach ($data['participants'] as $participant) {

                        fputcsv($file, [
                            'PARTICIPANTE',
                            '',
                            '',
                            '',
                            $clean($participant['nombre'] ?? '—'),
                            $clean($participant['email'] ?? '—'),
                            $clean($participant['celular'] ?? '—'),
                            $clean($participant['tipo'] ?? '—'),
                            $clean($participant['generacion'] ?? '—'),
                            '',
                            '',
                        ]);
                    }

                    fputcsv($file, []);
                    continue;
                }

                /*
                =====================================================
                GOLF (modelo viejo + nuevo)
                =====================================================
                */

                if ($registration->players && $registration->players->count() > 0) {

                    fputcsv($file, [
                        'INSCRIPCIÓN',
                        $evento,
                        $clean($registration->team_name),
                        $clean($instance->email),
                        $fecha,

                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',

                        $registration->subtotal,
                        $registration->total,
                    ]);

                    foreach ($registration->players as $index => $player) {

                        fputcsv($file, [
                            'JUGADOR',
                            '',
                            '',
                            '',
                            '',

                            $clean($player->name),
                            $clean($player->email),
                            $clean($player->phone),
                            $clean($player->campo),
                            $clean($player->handicap),
                            $clean($player->ghin),
                            $clean($player->shirt),
                            $player->is_captain ? 'Sí' : 'No',
                            $clean($player->cumbres),

                            '',
                            '',
                        ]);
                    }
                } elseif ($registration->form_data && isset($registration->form_data['players'])) {

                    $data = $registration->form_data;

                    fputcsv($file, [
                        'INSCRIPCIÓN',
                        $evento,
                        $clean($data['team_name'] ?? 'Equipo'),
                        $clean($instance->email),
                        $fecha,

                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',

                        $registration->subtotal,
                        $registration->total,
                    ]);

                    foreach ($data['players'] as $index => $player) {

                        fputcsv($file, [
                            'JUGADOR',
                            '',
                            '',
                            '',
                            '',

                            $clean($player['name'] ?? '—'),
                            $clean($player['email'] ?? '—'),
                            $clean($player['phone'] ?? '—'),
                            $clean($player['campo'] ?? '—'),
                            $clean($player['handicap'] ?? '—'),
                            $clean($player['ghin'] ?? '—'),
                            $clean($player['shirt'] ?? '—'),
                            $index === 0 ? 'Sí' : 'No',
                            $clean($player['cumbres'] ?? '—'),

                            '',
                            '',
                        ]);
                    }
                }

                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
