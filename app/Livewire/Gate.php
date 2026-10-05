<?php

namespace App\Livewire;

use App\Services\AttendanceService;
use Livewire\Component;

/** Gate scanner: USB/Bluetooth scanners type the code + Enter into the box; the camera feeds the same method. */
class Gate extends Component
{
    public string $code = '';
    /** @var list<array{name:string,status:string,time:string,duplicate:bool}> */
    public array $recent = [];
    public ?string $error = null;

    public function scan(?string $scanned = null): void
    {
        $service = app(AttendanceService::class);
        $code = trim($scanned ?? $this->code);
        $this->code = '';
        $this->error = null;
        if ($code === '') {
            return;
        }

        // Numeric-only codes are treated as fingerprint ids; long tokens as QR.
        $result = ctype_digit($code) && strlen($code) < 20
            ? $service->scan(null, $code, auth()->user())
            : $service->scan($code, null, auth()->user());

        if (! $result) {
            $this->error = __('Unknown student.');
            $this->dispatch('scanned', ok: false);

            return;
        }

        array_unshift($this->recent, [
            'name' => $result['student']->name,
            'status' => $result['attendance']->status,
            'time' => now()->format('H:i:s'),
            'duplicate' => $result['duplicate'],
        ]);
        $this->recent = array_slice($this->recent, 0, 12);
        $this->dispatch('scanned', ok: true);
    }

    public function render()
    {
        return view('livewire.gate')->title(__('Gate scanner'));
    }
}
