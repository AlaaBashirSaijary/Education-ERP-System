<?php

namespace App\Services\Messaging;

use App\Support\PhoneNumber;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Meta WhatsApp Cloud API (free-form text; for out-of-window messages switch to approved templates). */
class WhatsAppCloudChannel implements Channel
{
    public function send(string $phone, string $message): void
    {
        $cfg = config('services.whatsapp');
        if (! $cfg['token'] || ! $cfg['phone_number_id']) {
            throw new RuntimeException('WhatsApp is not configured: set WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID.');
        }

        try {
            Http::withToken($cfg['token'])->timeout(15)
                ->post("https://graph.facebook.com/v20.0/{$cfg['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => PhoneNumber::whatsapp($phone),
                    'type' => 'text',
                    'text' => ['body' => $message],
                ])
                ->throw();
        } catch (RequestException $e) {
            // Keep the provider's own explanation: "token expired", "number not in allowed list", ...
            $reason = $e->response->json('error.message') ?? $e->response->body();
            throw new RuntimeException('WhatsApp: '.mb_substr((string) $reason, 0, 300), $e->response->status(), $e);
        }
    }
}
