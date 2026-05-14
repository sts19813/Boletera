<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Eventos;
use App\Services\EventReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class RegistrationController extends Controller
{
    public function index(Request $request, ?string $event = null, EventReportService $reportService)
    {
        $user = $request->user();
        $events = $this->resolveAccessibleEvents($request)->sortBy('name')->values();

        if (!$event) {
            return view('admin.registrations.index', [
                'events' => $events,
                'selectedEvent' => null,
                'columns' => [],
                'rows' => [],
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
        ]);
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
