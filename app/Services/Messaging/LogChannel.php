<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Log;

class LogChannel implements Channel
{
    public function send(string $phone, string $message): void
    {
        Log::info("[messaging] to {$phone}: {$message}");
    }
}
