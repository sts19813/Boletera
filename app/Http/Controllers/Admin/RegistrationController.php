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

                /**
                 * ==========================
                 * FILA PADRE (INSCRIPCIÓN)
                 * ==========================
                 */
                fputcsv($file, [
                    'INSCRIPCIÓN',
                    $instance->evento?->name,
                    $registration->team_name,
                    $instance->email,
                    optional($instance->registered_at)->format('d/m/Y H:i'),

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

                /**
                 * ==========================
                 * FILAS HIJAS (JUGADORES)
                 * ==========================
                 */
                foreach ($registration->players as $player) {

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
                        implode(', ', $player->cumbres ?? []),

                        '',
                        '',
                        '',
                    ]);
                }

                /**
                 * Línea en blanco para separar equipos
                 */
                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
