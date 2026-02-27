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

    public function export()
    {
        $instances = RegistrationInstance::with([
            'evento',
            'registration.players'
        ])->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=inscripciones.csv',
        ];

        $callback = function () use ($instances) {

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

                $evento = $instance->evento?->name ?? '—';
                $fecha = optional($instance->registered_at)->format('d/m/Y H:i');

                /*
                =====================================================
                1️⃣ MODELO VIEJO (tabla players)
                =====================================================
                */
                if ($registration->players && $registration->players->count() > 0) {

                    // Fila padre
                    fputcsv($file, [
                        'INSCRIPCIÓN',
                        $evento,
                        $registration->team_name,
                        $instance->email,
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

                    // Hijos
                    foreach ($registration->players as $index => $player) {

                        fputcsv($file, [
                            'JUGADOR',
                            '',
                            '',
                            '',
                            '',

                            $player->name,
                            $player->email,
                            $player->phone,
                            $player->campo,
                            $player->handicap,
                            $player->ghin,
                            $player->shirt,
                            $player->is_captain ? 'Sí' : 'No',
                            is_array($player->cumbres)
                            ? implode(', ', $player->cumbres)
                            : $player->cumbres,

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

                    // -------------------------
                    // EVENTO GOLF (players)
                    // -------------------------
                    if (isset($data['players']) && count($data['players']) > 0) {

                        fputcsv($file, [
                            'INSCRIPCIÓN',
                            $evento,
                            $data['team_name'] ?? 'Equipo',
                            $instance->email,
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

                                $player['name'] ?? '—',
                                $player['email'] ?? '—',
                                $player['phone'] ?? '—',
                                $player['campo'] ?? '—',
                                $player['handicap'] ?? '—',
                                $player['ghin'] ?? '—',
                                $player['shirt'] ?? '—',
                                $index === 0 ? 'Sí' : 'No',
                                isset($player['cumbres'])
                                ? implode(', ', $player['cumbres'])
                                : '—',

                                '',
                                '',
                            ]);
                        }
                    }

                    // -------------------------
                    // EVENTO CENA (participants)
                    // -------------------------
                    elseif (isset($data['participants']) && count($data['participants']) > 0) {

                        fputcsv($file, [
                            'INSCRIPCIÓN',
                            $evento,
                            'Invitados',
                            $instance->email,
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

                                $participant['nombre'] ?? '—',
                                $participant['email'] ?? '—',
                                $participant['celular'] ?? '—',
                                $participant['tipo'] ?? '—',
                                $participant['generacion'] ?? '—',
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

                // Línea en blanco separadora
                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
