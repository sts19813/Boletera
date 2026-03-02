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
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=inscripciones.csv',
        ];

        $callback = function () use ($instances) {

            $clean = function ($value) {
                if (is_array($value)) {
                    return implode(', ', array_map(fn($v) => strip_tags((string) $v), $value));
                }
                return is_string($value) ? strip_tags($value) : $value;
            };

            $file = fopen('php://output', 'w');

            // BOM UTF-8 para Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

            foreach ($instances as $instance) {

                $registration = $instance->registration;

                if (!$registration) {
                    continue;
                }

                $evento = $clean($instance->evento?->name ?? '—');
                $fecha = optional($instance->registered_at)->format('d/m/Y H:i');

                /*
                =====================================================
                1️⃣ MODELO VIEJO (tabla players)
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
                }

                /*
                =====================================================
                2️⃣ MODELO NUEVO (JSON form_data)
                =====================================================
                */ elseif ($registration->form_data) {

                    $data = $registration->form_data;

                    /*
                    =========================
                    EVENTO GOLF (players)
                    =========================
                    */
                    if (isset($data['players']) && count($data['players']) > 0) {

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

                    /*
                    =========================
                    EVENTO CENA (participants)
                    =========================
                    */ elseif (isset($data['participants']) && count($data['participants']) > 0) {

                        fputcsv($file, [
                            'INSCRIPCIÓN',
                            $evento,
                            'Invitados',
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

                        foreach ($data['participants'] as $participant) {

                            fputcsv($file, [
                                'PARTICIPANTE',
                                '',
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
                                '',
                                '',
                                '',
                                '',
                            ]);
                        }
                    }
                }

                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
