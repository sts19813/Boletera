<?php

namespace App\Jobs;

use App\Mail\BoletosMail;
use App\Mail\DirectRegistrationMail;
use App\Models\Eventos;
use App\Models\QueueMailTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ProcessQueueMailTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 180;
    public array $backoff = [15, 60, 180];

    public function __construct(public string $taskId)
    {
        $this->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'));
    }

    public function handle(): void
    {
        $task = QueueMailTask::find($this->taskId);

        if (!$task || $task->status === 'sent') {
            return;
        }

        $task->update([
            'status' => 'processing',
            'attempts' => ((int) $task->attempts) + 1,
            'processed_at' => now(),
            'error_message' => null,
        ]);

        try {
            $payload = $task->payload ?? [];

            if ($task->type === 'direct_registration') {
                $eventId = (string) data_get($payload, 'event_id', '');
                $event = Eventos::findOrFail($eventId);
                $registrationData = (array) data_get($payload, 'registration_data', []);
                Mail::to($task->recipient)->sendNow(new DirectRegistrationMail($event, $registrationData));
            } else {
                $boletos = (array) data_get($payload, 'boletos', []);
                if (empty($boletos)) {
                    throw new \RuntimeException('La tarea no contiene boletos para enviar.');
                }

                $subject = data_get($payload, 'subject');
                $mail = new BoletosMail($boletos, is_string($subject) ? $subject : null);
                Mail::to($task->recipient)->sendNow($mail);
            }

            $task->update([
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'error_message' => null,
            ]);
        } catch (Throwable $e) {
            $task->update([
                'status' => 'failed',
                'failed_at' => now(),
                'error_message' => Str::limit($e->getMessage(), 1900),
            ]);

            throw $e;
        }
    }
}

