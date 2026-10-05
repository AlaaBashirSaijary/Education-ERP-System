<?php

namespace App\Livewire;

use App\Models\NotificationLog;
use Livewire\Component;
use Livewire\WithPagination;

class Messages extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.messages', [
            'logs' => NotificationLog::with('student')->latest()->paginate(20),
        ])->title(__('Parent messages'));
    }
}
