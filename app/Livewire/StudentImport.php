<?php

namespace App\Livewire;

use App\Services\StudentImporter;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Admin: upload a CSV, review what would happen, then import. */
class StudentImport extends Component
{
    use WithFileUploads;

    public $file = null;
    public bool $updateExisting = false;
    public bool $createClasses = false;
    public ?array $result = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function updatedFile(): void
    {
        $this->validate(['file' => 'file|max:4096|extensions:csv,txt']);
        $this->result = null;
    }

    private function analysis(): ?array
    {
        return $this->file
            ? app(StudentImporter::class)->analyse($this->file->getRealPath(), $this->updateExisting, $this->createClasses)
            : null;
    }

    public function import(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
        $a = $this->analysis();
        if (! $a || $a['missing']) {
            return;
        }

        $this->result = app(StudentImporter::class)->commit($a['rows'], $this->createClasses);
        $this->reset('file');
    }

    public function render()
    {
        $a = $this->analysis();
        $counts = $a ? collect($a['rows'])->countBy('status')->all() : [];

        return view('livewire.student-import', ['a' => $a, 'counts' => $counts])->title(__('Import students'));
    }
}
