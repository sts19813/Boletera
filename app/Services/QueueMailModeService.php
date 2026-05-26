<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class QueueMailModeService
{
    public const MODE_QUEUE = 'queue';
    public const MODE_SYNC = 'sync';
    public const SETTING_KEY = 'queue_mail_delivery_mode';

    public function getPreferredMode(): string
    {
        if (!$this->settingsTableExists()) {
            return self::MODE_QUEUE;
        }

        try {
            $mode = (string) AppSetting::getValue(self::SETTING_KEY, self::MODE_QUEUE);
        } catch (Throwable) {
            return self::MODE_QUEUE;
        }

        return in_array($mode, [self::MODE_QUEUE, self::MODE_SYNC], true)
            ? $mode
            : self::MODE_QUEUE;
    }

    public function setPreferredMode(string $mode): void
    {
        if (!in_array($mode, [self::MODE_QUEUE, self::MODE_SYNC], true)) {
            return;
        }

        if (!$this->settingsTableExists()) {
            return;
        }

        try {
            AppSetting::putValue(self::SETTING_KEY, $mode);
        } catch (Throwable) {
            // keep current behavior when settings persistence is unavailable
        }
    }

    public function resolveEffectiveMode(): string
    {
        if ($this->getPreferredMode() === self::MODE_SYNC) {
            return self::MODE_SYNC;
        }

        return $this->isQueueConfigurationValid()
            ? self::MODE_QUEUE
            : self::MODE_SYNC;
    }

    public function isQueueConfigurationValid(): bool
    {
        return $this->queueConnectionFromEnv() === 'database'
            && $this->queueNameFromEnv() === 'ticket-delivery';
    }

    public function queueConnection(): string
    {
        return (string) config('queue.default', '');
    }

    public function queueName(): string
    {
        return (string) config('queue.ticket_delivery_queue', 'ticket-delivery');
    }

    public function queueConnectionFromEnv(): ?string
    {
        $value = config('queue.default_from_env');
        return is_string($value) ? $value : null;
    }

    public function queueNameFromEnv(): ?string
    {
        $value = config('queue.ticket_delivery_queue_from_env');
        return is_string($value) ? $value : null;
    }

    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('app_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
