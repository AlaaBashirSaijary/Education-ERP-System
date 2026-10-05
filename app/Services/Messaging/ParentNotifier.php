<?php

namespace App\Services\Messaging;

use App\Jobs\SendParentMessage;
use App\Models\NotificationLog;
use App\Models\Student;
use InvalidArgumentException;

/** Queues a message to a student's parent. The queued job does the actual sending. */
class ParentNotifier
{
    public function notify(Student $student, string $type, string $message): ?NotificationLog
    {
        $phone = $student->notifyPhone();
        if (! $phone) {
            return null;
        }

        // A public demo must never message real phone numbers, whatever the environment says.
        $channel = config('school.demo_mode') ? 'log' : config('messaging.channel');

        $log = NotificationLog::create([
            'student_id' => $student->id,
            'phone' => $phone,
            'channel' => $channel,
            'type' => $type,
            'message' => $message,
        ]);

        SendParentMessage::dispatch($log->id);

        return $log;
    }

    public static function resolveChannel(string $name): Channel
    {
        return match ($name) {
            'whatsapp' => new WhatsAppCloudChannel,
            'sms' => new TwilioSmsChannel,
            'log' => new LogChannel,
            default => throw new InvalidArgumentException("Unknown messaging channel [{$name}]"),
        };
    }
}
