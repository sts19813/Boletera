<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Models\TicketInstance;
use App\Services\EventReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RegistrationController extends Controller
{
    public function index(Request $request, EventReportService $reportService, ?string $event = null)
    {
        $user = $request->user();
        $events = $this->resolveAccessibleEvents($request)->sortBy('name')->values();

        if (!$event) {
            return view('admin.registrations.index', [
                'events' => $events,
                'selectedEvent' => null,
                'columns' => [],
                'rows' => [],
                'canEditTickets' => false,
            ]);
        }

        $selectedEvent = $events->firstWhere('id', $event);

        if (!$selectedEvent) {
            abort(403, 'No tienes acceso a este evento.');
        }

        $report = $reportService->build($selectedEvent);

        return view('admin.registrations.index', [
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'canExportReports' => $this->userCan($user, 'exportar reportes'),
            'canEditReport' => $this->userCan($user, 'editar reportes'),
            'canEditTickets' => $user?->hasRole('admin') && $this->userCan($user, 'editar tickets'),
        ]);
    }

    /**
     * Actualiza los detalles de un ticket específico.
     *
     * @param Request $request
     * @param TicketInstance $instance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateTicket(Request $request, TicketInstance $instance)
    {
        $user = $request->user();

        if (!$user?->hasRole('admin') || !$this->userCan($user, 'editar tickets')) {
            abort(403, 'No tienes permiso para editar tickets.');
        }

        if ($instance->sale_type === 'registration') {
            abort(404, 'Esta accion solo aplica para boletos.');
        }

        $validated = $request->validate([
            'nombre' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:card,cash'],
            'sale_channel' => ['required', 'in:stripe,taquilla'],
            'is_cortesia' => ['nullable', 'boolean'],
        ]);

        $price = round((float) $validated['price'], 2);

        $instance->update([
            'nombre' => $validated['nombre'] ? trim($validated['nombre']) : null,
            'email' => $request->boolean('is_cortesia')
                ? 'CORTESIA'
                : ($validated['email'] ? trim($validated['email']) : null),
            'price' => $price,
            'subtotal' => $price,
            'total' => $price,
            'payment_method' => $validated['payment_method'],
            'sale_channel' => $validated['sale_channel'],
        ]);

        return back()->with('success', 'Ticket actualizado correctamente.');
    }

    public function export(Request $request, string $event, EventReportService $reportService)
    {
        if (!$this->userCan($request->user(), 'exportar reportes')) {
            abort(403, 'No tienes permiso para exportar reportes.');
        }

        $selectedEvent = $this->resolveAccessibleEvents($request)->firstWhere('id', $event);

        if (!$selectedEvent) {
            abort(403, 'No tienes acceso a este evento.');
        }

        $report = $reportService->build($selectedEvent);
        $headers = array_map(fn(array $column) => $column['label'], $report['columns']);
        $keys = array_map(fn(array $column) => $column['key'], $report['columns']);

        $filename = 'reporte_' . Str::slug($selectedEvent->name) . '_' . now()->format('Ymd_His') . '.csv';
        $responseHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($headers, $keys, $report) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, $headers);

            foreach ($report['rows'] as $row) {
                $csvRow = [];
                foreach ($keys as $key) {
                    $value = $row[$key] ?? '-';
                    $csvRow[] = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                fputcsv($file, $csvRow);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $responseHeaders);
    }

    /**
     * @return Collection<int, Eventos>
     */
    private function resolveAccessibleEvents(Request $request): Collection
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return Eventos::query()->get();
        }

        $allowedEventIds = $user?->events()->pluck('eventos.id') ?? collect();

        return Eventos::query()
            ->whereIn('id', $allowedEventIds)
            ->get();
    }

    private function userCan($user, string $permission): bool
    {
        if (!$user) {
            return false;
        }

        $permissionExists = Permission::query()
            ->where('name', $permission)
            ->exists();

        return $permissionExists && $user->can($permission);
    }
}
