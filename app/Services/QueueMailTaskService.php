<?php

namespace App\Services;

use App\Jobs\ProcessQueueMailTaskJob;
use App\Models\QueueMailTask;
use Throwable;

class QueueMailTaskService
{
    public function __construct(
        private QueueMailModeService $queueMailModeService
    ) {
    }

    public function queueBoletos(
        string $recipient,
        array $boletos,
        string $type = 'boletos_purchase',
        ?string $reference = null,
        ?string $subject = null,
        bool $deduplicateByReference = false
    ): QueueMailTask {
        $normalizedRecipient = strtolower(trim($recipient));

        if ($deduplicateByReference && $reference) {
            $existing = QueueMailTask::query()
                ->where('type', $type)
                ->where('recipient', $normalizedRecipient)
                ->where('reference', $reference)
                ->whereIn('status', ['pending', 'processing', 'sent'])
                ->latest('created_at')
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $shouldQueue = $this->shouldQueueDispatch();
        $queueName = $this->resolveQueueName($shouldQueue);

        $task = QueueMailTask::create([
            'type' => $type,
            'recipient' => $normalizedRecipient,
            'reference' => $reference,
            'queue_name' => $queueName,
            'status' => 'pending',
            'attempts' => 0,
            'payload' => [
                'boletos' => $boletos,
                'subject' => $subject,
                'boletos_count' => count($boletos),
            ],
            'queued_at' => now(),
        ]);

        if ($shouldQueue) {
            ProcessQueueMailTaskJob::dispatch($task->id)
                ->onQueue($queueName)
                ->afterCommit();

            return $task;
        }

        return $this->processSynchronously($task);
    }

    public function queueDirectRegistration(
        string $recipient,
        string $eventId,
        array $registrationData,
        ?string $reference = null
    ): QueueMailTask {
        $shouldQueue = $this->shouldQueueDispatch();
        $queueName = $this->resolveQueueName($shouldQueue);

        $task = QueueMailTask::create([
            'type' => 'direct_registration',
            'recipient' => strtolower(trim($recipient)),
            'reference' => $reference,
            'queue_name' => $queueName,
            'status' => 'pending',
            'attempts' => 0,
            'payload' => [
                'event_id' => $eventId,
                'registration_data' => $registrationData,
            ],
            'queued_at' => now(),
        ]);

        if ($shouldQueue) {
            ProcessQueueMailTaskJob::dispatch($task->id)
                ->onQueue($queueName)
                ->afterCommit();

            return $task;
        }

        return $this->processSynchronously($task);
    }

    public function retryTask(QueueMailTask $task): QueueMailTask
    {
        $shouldQueue = $this->shouldQueueDispatch();
        $queueName = $this->resolveQueueName($shouldQueue);

        $task->update([
            'status' => 'pending',
            'queue_name' => $queueName,
            'failed_at' => null,
            'error_message' => null,
            'queued_at' => now(),
        ]);

        if ($shouldQueue) {
            ProcessQueueMailTaskJob::dispatch($task->id)
                ->onQueue($queueName)
                ->afterCommit();

            return $task;
        }

        return $this->processSynchronously($task);
    }

    private function shouldQueueDispatch(): bool
    {
        return $this->queueMailModeService->resolveEffectiveMode() === QueueMailModeService::MODE_QUEUE;
    }

    private function resolveQueueName(bool $shouldQueue): string
    {
        return $shouldQueue
            ? $this->queueMailModeService->queueName()
            : QueueMailModeService::MODE_SYNC;
    }

    private function processSynchronously(QueueMailTask $task): QueueMailTask
    {
        try {
            (new ProcessQueueMailTaskJob($task->id))->handle();
        } catch (Throwable $e) {
            report($e);
        }

        return $task->fresh() ?? $task;
    }
}
