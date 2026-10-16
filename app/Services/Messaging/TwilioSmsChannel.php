<?php

namespace App\Services\Messaging;

use App\Support\PhoneNumber;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioSmsChannel implements Channel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('services.twilio');
        if (! $cfg['sid'] || ! $cfg['token'] || ! $cfg['from']) {
            throw new RuntimeException('SMS is not configured: set TWILIO_SID, TWILIO_TOKEN and TWILIO_FROM.');
        }

        try {
            Http::withBasicAuth($cfg['sid'], $cfg['token'])->timeout(15)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['sid']}/Messages.json", [
                    'To' => PhoneNumber::e164($phone),
                    'From' => $cfg['from'],
                    'Body' => $message,
                ])
                ->throw();
        } catch (RequestException $e) {
            $reason = $e->response->json('message') ?? $e->response->body();
            throw new RuntimeException('SMS: '.mb_substr((string) $reason, 0, 300), $e->response->status(), $e);
        }
    }
}
