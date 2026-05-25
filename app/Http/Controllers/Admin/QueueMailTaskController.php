<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueMailTask;
use App\Services\QueueMailTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueMailTaskController extends Controller
{
    public function __construct(
        private QueueMailTaskService $queueMailTaskService
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

        $stats = [
            'pending' => QueueMailTask::where('status', 'pending')->count(),
            'processing' => QueueMailTask::where('status', 'processing')->count(),
            'sent' => QueueMailTask::where('status', 'sent')->count(),
            'failed' => QueueMailTask::where('status', 'failed')->count(),
            'jobs_table_pending' => $pendingInJobsTable,
        ];

        return view('admin.queue_mail_tasks.index', compact('tasks', 'stats', 'status', 'search', 'queueName'));
    }

    public function runPending(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_jobs' => 'nullable|integer|min:1|max:10',
        ]);

        $maxJobs = (int) ($validated['max_jobs'] ?? 3);
        $queueName = config('queue.ticket_delivery_queue', 'ticket-delivery');
        $connection = 'database';
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

    public function retry(QueueMailTask $task): RedirectResponse
    {
        $this->queueMailTaskService->retryTask($task);

        return back()->with('success', 'Tarea reenviada a la cola.');
    }
}
