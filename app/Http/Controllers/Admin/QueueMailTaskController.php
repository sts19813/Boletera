<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueMailTask;
use App\Services\QueueMailModeService;
use App\Services\QueueMailTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueMailTaskController extends Controller
{
    public function __construct(
        private QueueMailTaskService $queueMailTaskService,
        private QueueMailModeService $queueMailModeService
    ) {
    }

    public function index(Request $request)
    {
        $status = (string) $request->get('status', '');
        $search = trim((string) $request->get('search', ''));

        $tasks = QueueMailTask::query()
            ->when(in_array($status, ['pending', 'processing', 'sent', 'failed'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('recipient', 'like', '%' . $search . '%')
                        ->orWhere('reference', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%');
                });
            })
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $queueName = config('queue.ticket_delivery_queue', 'ticket-delivery');
        $pendingInJobsTable = DB::table('jobs')
            ->where('queue', $queueName)
            ->count();
        $preferredMode = $this->queueMailModeService->getPreferredMode();
        $effectiveMode = $this->queueMailModeService->resolveEffectiveMode();
        $queueConnection = $this->queueMailModeService->queueConnection();
        $queueConnectionEnv = $this->queueMailModeService->queueConnectionFromEnv();
        $queueNameEnv = $this->queueMailModeService->queueNameFromEnv();
        $queueConfigValid = $this->queueMailModeService->isQueueConfigurationValid();

        $stats = [
            'pending' => QueueMailTask::where('status', 'pending')->count(),
            'processing' => QueueMailTask::where('status', 'processing')->count(),
            'sent' => QueueMailTask::where('status', 'sent')->count(),
            'failed' => QueueMailTask::where('status', 'failed')->count(),
            'jobs_table_pending' => $pendingInJobsTable,
        ];

        return view('admin.queue_mail_tasks.index', compact(
            'tasks',
            'stats',
            'status',
            'search',
            'queueName',
            'preferredMode',
            'effectiveMode',
            'queueConnection',
            'queueConnectionEnv',
            'queueNameEnv',
            'queueConfigValid'
        ));
    }

    public function runPending(Request $request): RedirectResponse
    {
        if ($this->queueMailModeService->resolveEffectiveMode() !== QueueMailModeService::MODE_QUEUE) {
            return back()->with('success', 'Modo actual en sync: no hay tareas en cola para procesar con worker.');
        }

        $validated = $request->validate([
            'max_jobs' => 'nullable|integer|min:1|max:10',
        ]);

        $maxJobs = (int) ($validated['max_jobs'] ?? 3);
        $queueName = $this->queueMailModeService->queueName();
        $connection = $this->queueMailModeService->queueConnection();
        $executed = 0;

        for ($i = 0; $i < $maxJobs; $i++) {
            $pendingJobs = DB::table('jobs')
                ->where('queue', $queueName)
                ->count();

            if ($pendingJobs === 0) {
                break;
            }

            Artisan::call('queue:work', [
                'connection' => $connection,
                '--queue' => $queueName,
                '--once' => true,
                '--tries' => 5,
                '--timeout' => 180,
                '--sleep' => 1,
            ]);

            $executed++;
        }

        return back()->with('success', "Se ejecuto el worker manual para {$executed} tarea(s).");
    }

    public function updateDeliveryMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_mode' => 'required|in:queue,sync',
        ]);

        $selectedMode = (string) $validated['delivery_mode'];
        $this->queueMailModeService->setPreferredMode($selectedMode);
        $effectiveMode = $this->queueMailModeService->resolveEffectiveMode();

        if ($selectedMode === QueueMailModeService::MODE_QUEUE && $effectiveMode === QueueMailModeService::MODE_SYNC) {
            return back()->with('success', 'Se selecciono queue, pero al no cumplir la configuracion requerida se aplicara sync automaticamente.');
        }

        $label = $effectiveMode === QueueMailModeService::MODE_QUEUE ? 'queue' : 'sync';
        return back()->with('success', "Modo de envio actualizado. Modo efectivo: {$label}.");
    }

    public function retry(QueueMailTask $task): RedirectResponse
    {
        $this->queueMailTaskService->retryTask($task);

        return back()->with('success', 'Tarea reenviada a la cola.');
    }
}
