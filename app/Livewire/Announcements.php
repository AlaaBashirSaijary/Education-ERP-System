<?php

namespace App\Livewire;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Messaging\ParentNotifier;
use Livewire\Component;

/** Broadcast a message to parents: whole school or one class. One message per phone number (siblings share it). */
class Announcements extends Component
{
    public string $audience = 'all';
    public string $message = '';

    public function send(ParentNotifier $notifier): void
    {
        $d = $this->validate([
            'audience' => ['required', fn ($a, $v, $fail) => $v === 'all' || SchoolClass::whereKey($v)->exists() ?: $fail(__('Invalid audience.'))],
            'message' => 'required|string|min:3|max:500',
        ]);

        $students = Student::with('parent')->where('active', true)
            ->when($d['audience'] !== 'all', fn ($q) => $q->where('school_class_id', $d['audience']))->get();

        $sent = 0;
        foreach ($students->unique(fn (Student $s) => $s->notifyPhone() ?: 'none-'.$s->id) as $s) {
            if ($notifier->notify($s, 'announcement', $d['message'])) {
                $sent++;
            }
        }

        $this->reset('message');
        session()->flash($sent ? 'ok' : 'warn', $sent ? __('Message queued for :n parents.', ['n' => $sent]) : __('No parent phone numbers found for this audience.'));
    }

    public function render()
    {
        return view('livewire.announcements', [
            'classes' => SchoolClass::orderBy('name')->orderBy('section')->get(),
        ])->title(__('Announcements'));
    }
}
