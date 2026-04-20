<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\TicketInstance;

class RegistrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($event = null)
    {
        $user = auth()->user();

        $registrations = TicketInstance::registrationSales()->with(['evento']);
        $tickets = TicketInstance::ticketSales()->with(['ticket', 'evento']);

        if (!$user->hasRole('admin')) {
            $allowedEventIds = $user->events()->pluck('eventos.id');
            $registrations->whereIn('event_id', $allowedEventIds);
            $tickets->whereIn('event_id', $allowedEventIds);
        }

        if ($event) {
            $registrations->where('event_id', $event);
            $tickets->where('event_id', $event);
        }

        $registrations = $registrations->get();
        $tickets = $tickets->get();

        $sales = collect()
            ->merge($registrations->map(function ($r) {
                return [
                    'type' => 'registration',
                    'email' => $r->email,
                    'event' => $r->evento?->name,
                    'date' => $r->purchased_at,
                    'model' => $r,
                ];
            }))
            ->merge($tickets->map(function ($t) {
                return [
                    'type' => 'ticket',
                    'email' => $t->email,
                    'event' => $t->evento?->name ?? '-',
                    'date' => $t->purchased_at,
                    'model' => $t,
                ];
            }))
            ->sortByDesc('date')
            ->values();

        $events = $user->hasRole('admin')
            ? Eventos::all()
            : $user->events;

        return view('admin.registrations.index', compact('sales', 'events'));
    }

    public function export($eventId)
    {
        $registrations = TicketInstance::registrationSales()
            ->with(['evento'])
            ->where('event_id', $eventId)
            ->get()
            ->unique('payment_intent_id')
            ->values();

        $tickets = TicketInstance::ticketSales()
            ->with(['evento', 'ticket'])
            ->where('event_id', $eventId)
            ->get();

        $instances = collect();

        foreach ($registrations as $r) {
            $instances->push([
                'type' => 'registration',
                'model' => $r,
            ]);
        }

        foreach ($tickets as $t) {
            $instances->push([
                'type' => 'ticket',
                'model' => $t,
            ]);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=inscripciones.csv',
        ];

        $isMesaEvent = $eventId === '019c8c31-f771-709d-817f-500abcb8c03a';
        $isCenaGalaEvent = $eventId === '019c91a4-9f3b-7039-93cc-83f50c44c835';
        $hasWhatsappDirectRegistrations = $registrations->contains(function ($registration) {
            $data = is_array($registration->form_data) ? $registration->form_data : [];

            return ($data['template_form'] ?? null) === 'whatsapp_direct'
                || isset($data['full_name'])
                || isset($data['game_id']);
        });

        $callback = function () use ($instances, $isMesaEvent, $isCenaGalaEvent, $hasWhatsappDirectRegistrations) {
            $clean = function ($value) {
                if (is_array($value)) {
                    return implode(', ', array_map(fn($v) => strip_tags((string) $v), $value));
                }
                return is_string($value) ? strip_tags($value) : $value;
            };

            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($isMesaEvent) {
                fputcsv($file, [
                    'Tipo',
                    'Evento',
                    'Mesa',
                    'Nombre',
                    'Email',
                    'Celular',
                    'Fecha Compra',
                    'Subtotal',
                    'Total',
                ]);
            } elseif ($isCenaGalaEvent) {
                fputcsv($file, [
                    'Tipo',
                    'Evento',
                    'Email Registro',
                    'Fecha Registro',
                    'Nombre',
                    'Email',
                    'Celular',
                    'Tipo Invitado',
                    'Generacion',
                    'Subtotal',
                    'Total',
                ]);
            } elseif ($hasWhatsappDirectRegistrations) {
                fputcsv($file, [
                    'Tipo',
                    'Evento',
                    'Fecha Registro',
                    'Nombre completo',
                    'Edad',
                    'Ciudad',
                    'Estado',
                    'Telefono',
                    'Correo electronico',
                    'ID del juego',
                    'Consola',
                    'Participacion previa',
                    'Cuantas veces',
                    'Como nos conocio',
                    'Usuario Twitch/YouTube',
                    'Recibo',
                    'Referencia',
                    'Subtotal',
                    'Total',
                ]);
            } else {
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
                    'Capitan',
                    'Relacion Cumbres',
                    'Metodo Pago',
                    'Subtotal',
                    'Total',
                ]);
            }

            foreach ($instances as $row) {
                $instance = $row['model'];

                if ($isMesaEvent) {
                    $evento = $clean($instance->evento?->name ?? '-');
                    $mesa = $clean($instance->ticket?->name ?? '-');
                    $fecha = optional($instance->purchased_at)->format('d/m/Y H:i');

                    fputcsv($file, [
                        'TICKET',
                        $evento,
                        $mesa,
                        $clean($instance->nombre),
                        $clean($instance->email),
                        $clean($instance->celular),
                        $fecha,
                        $instance->subtotal ?? $instance->price,
                        $instance->total ?? $instance->price,
                    ]);

                    continue;
                }

                if ($row['type'] !== 'registration') {
                    continue;
                }

                $evento = $clean($instance->evento?->name ?? '-');
                $fecha = optional($instance->purchased_at)->format('d/m/Y H:i');
                $data = is_array($instance->form_data) ? $instance->form_data : [];

                if ($isCenaGalaEvent) {
                    if (!isset($data['participants']) || !is_array($data['participants'])) {
                        continue;
                    }

                    fputcsv($file, [
                        'INSCRIPCION',
                        $evento,
                        $clean($instance->email),
                        $fecha,
                        '',
                        '',
                        '',
                        '',
                        '',
                        $instance->subtotal,
                        $instance->total,
                    ]);

                    foreach ($data['participants'] as $participant) {
                        fputcsv($file, [
                            'PARTICIPANTE',
                            '',
                            '',
                            '',
                            $clean($participant['nombre'] ?? '-'),
                            $clean($participant['email'] ?? '-'),
                            $clean($participant['celular'] ?? '-'),
                            $clean($participant['tipo'] ?? '-'),
                            $clean($participant['generacion'] ?? '-'),
                            '',
                            '',
                        ]);
                    }

                    fputcsv($file, []);
                    continue;
                }

                if ($hasWhatsappDirectRegistrations) {
                    $receipt = $data['receipt_file_url'] ?? $data['receipt_file_path'] ?? '-';

                    fputcsv($file, [
                        'INSCRIPCION',
                        $evento,
                        $fecha,
                        $clean($data['full_name'] ?? $instance->nombre ?? '-'),
                        $clean($data['age'] ?? '-'),
                        $clean($data['city'] ?? '-'),
                        $clean($data['state'] ?? '-'),
                        $clean($data['phone'] ?? $instance->celular ?? '-'),
                        $clean($data['email'] ?? $instance->email ?? '-'),
                        $clean($data['game_id'] ?? '-'),
                        $clean($data['console'] ?? '-'),
                        $clean($data['participated_before'] ?? '-'),
                        $clean($data['participation_count'] ?? '-'),
                        $clean($data['how_known_label'] ?? $data['how_known'] ?? '-'),
                        $clean($data['stream_user'] ?? '-'),
                        $clean($receipt),
                        $clean($instance->reference ?? '-'),
                        $instance->subtotal,
                        $instance->total,
                    ]);

                    continue;
                }

                if (!isset($data['players']) || !is_array($data['players']) || count($data['players']) === 0) {
                    continue;
                }

                $paymentMethod = match ($instance->payment_method) {
                    'cash' => 'Cash',
                    'card' => 'Card',
                    default => 'Card',
                };

                fputcsv($file, [
                    'INSCRIPCION',
                    $evento,
                    $clean($data['team_name'] ?? $instance->team_name ?? 'Equipo'),
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
                    $paymentMethod,
                    $instance->subtotal,
                    $instance->total,
                ]);

                foreach ($data['players'] as $index => $player) {
                    $captain = array_key_exists('is_captain', $player)
                        ? (bool) $player['is_captain']
                        : $index === 0;

                    fputcsv($file, [
                        'JUGADOR',
                        '',
                        '',
                        '',
                        '',
                        $clean($player['name'] ?? '-'),
                        $clean($player['email'] ?? '-'),
                        $clean($player['phone'] ?? '-'),
                        $clean($player['campo'] ?? '-'),
                        $clean($player['handicap'] ?? '-'),
                        $clean($player['ghin'] ?? '-'),
                        $clean($player['shirt'] ?? '-'),
                        $captain ? 'Si' : 'No',
                        $clean($player['cumbres'] ?? '-'),
                        '',
                        '',
                    ]);
                }

                fputcsv($file, []);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
