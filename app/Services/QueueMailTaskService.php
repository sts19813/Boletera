<?php

namespace App\Services;

use App\Jobs\ProcessQueueMailTaskJob;
use App\Models\QueueMailTask;

class QueueMailTaskService
{
    public function queueBoletos(
        string $recipient,
        array $boletos,
        string $type = 'boletos_purchase',
        ?string $reference = null,
        ?string $subject = null
    ): QueueMailTask {
        $task = QueueMailTask::create([
            'type' => $type,
            'recipient' => strtolower(trim($recipient)),
            'reference' => $reference,
            'queue_name' => config('queue.ticket_delivery_queue', 'ticket-delivery'),
            'status' => 'pending',
            'attempts' => 0,
            'payload' => [
                'boletos' => $boletos,
                'subject' => $subject,
                'boletos_count' => count($boletos),
            ],
            'queued_at' => now(),
        ]);

        ProcessQueueMailTaskJob::dispatch($task->id)
            ->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'))
            ->afterCommit();

        return $task;
    }

    public function queueDirectRegistration(
        string $recipient,
        string $eventId,
        array $registrationData,
        ?string $reference = null
    ): QueueMailTask {
        $task = QueueMailTask::create([
            'type' => 'direct_registration',
            'recipient' => strtolower(trim($recipient)),
            'reference' => $reference,
            'queue_name' => config('queue.ticket_delivery_queue', 'ticket-delivery'),
            'status' => 'pending',
            'attempts' => 0,
            'payload' => [
                'event_id' => $eventId,
                'registration_data' => $registrationData,
            ],
            'queued_at' => now(),
        ]);

        ProcessQueueMailTaskJob::dispatch($task->id)
            ->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'))
            ->afterCommit();

        return $task;
    }

    public function retryTask(QueueMailTask $task): QueueMailTask
    {
        $task->update([
            'status' => 'pending',
            'failed_at' => null,
            'error_message' => null,
            'queued_at' => now(),
        ]);

        ProcessQueueMailTaskJob::dispatch($task->id)
            ->onQueue(config('queue.ticket_delivery_queue', 'ticket-delivery'))
            ->afterCommit();

        return $task;
    }
}

