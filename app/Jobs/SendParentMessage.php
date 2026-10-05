<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\Messaging\ParentNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendParentMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public int $logId) {}

    public function handle(): void
    {
        $log = NotificationLog::findOrFail($this->logId);

        ParentNotifier::resolveChannel($log->channel)->send($log->phone, $log->message);

        $log->update(['status' => 'sent', 'error' => null]);
    }

    public function failed(Throwable $e): void
    {
        NotificationLog::whereKey($this->logId)->update(['status' => 'failed', 'error' => $e->getMessage()]);
    }
}
