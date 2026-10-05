<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

class TwilioSmsChannel implements Channel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('services.twilio');

        Http::withBasicAuth($cfg['sid'], $cfg['token'])
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['sid']}/Messages.json", [
                'To' => $phone,
                'From' => $cfg['from'],
                'Body' => $message,
            ])
            ->throw();
    }
}
