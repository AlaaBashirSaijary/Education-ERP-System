<?php

namespace App\Services\Messaging;

use Illuminate\Support\Facades\Http;

/** Meta WhatsApp Cloud API (free-form text; for out-of-window messages switch to approved templates). */
class WhatsAppCloudChannel implements Channel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('services.whatsapp');

        Http::withToken($cfg['token'])
            ->post("https://graph.facebook.com/v20.0/{$cfg['phone_number_id']}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => ltrim($phone, '+'),
                'type' => 'text',
                'text' => ['body' => $message],
            ])
            ->throw();
    }
}
