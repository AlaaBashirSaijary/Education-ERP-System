<?php

namespace App\Livewire;

use App\Models\NotificationLog;
use App\Services\Messaging\ParentNotifier;
use App\Support\PhoneNumber;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Messages extends Component
{
    use WithPagination;

    public string $testPhone = '';
    public ?array $testResult = null;

    /** What is set up, without ever showing a secret. */
    private function setup(): array
    {
        $channel = config('school.demo_mode') ? 'log' : config('messaging.channel');
        $needs = match ($channel) {
            'whatsapp' => ['WHATSAPP_TOKEN' => config('services.whatsapp.token'), 'WHATSAPP_PHONE_NUMBER_ID' => config('services.whatsapp.phone_number_id')],
            'sms' => ['TWILIO_SID' => config('services.twilio.sid'), 'TWILIO_TOKEN' => config('services.twilio.token'), 'TWILIO_FROM' => config('services.twilio.from')],
            default => [],
        };

        return ['channel' => $channel, 'missing' => array_keys(array_filter($needs, fn ($v) => ! $v)), 'country' => config('school.country_code')];
    }

    /** Sends one real message right now (not queued) so the admin sees the provider's answer. */
    public function sendTest(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $this->validate(['testPhone' => 'required|string|max:25']);

        $phone = PhoneNumber::normalize($this->testPhone);
        $channel = $this->setup()['channel'];

        try {
            if (! PhoneNumber::isValid($phone)) {
                throw new \RuntimeException(__('The phone number looks wrong.'));
            }
            ParentNotifier::resolveChannel($channel)->send($phone, __('Test message from :school. If you can read this, messaging works.', ['school' => config('school.name')]));
            $this->testResult = ['ok' => true, 'text' => $channel === 'log'
                ? __('Saved to the log only: the channel is "log", so nothing was sent to a phone. Set MESSAGING_CHANNEL to whatsapp or sms.')
                : __('Sent to :p through :c. Check that the phone received it.', ['p' => $phone, 'c' => $channel])];
        } catch (Throwable $e) {
            $this->testResult = ['ok' => false, 'text' => $e->getMessage()];
        }
    }

    public function render()
    {
        return view('livewire.messages', [
            'logs' => NotificationLog::with('student')->latest()->paginate(20),
            'setup' => $this->setup(),
        ])->title(__('Parent messages'));
    }
}
