<?php

namespace App\Support;

use App\Models\AcademicYear;

/** Resolves "the year being viewed" (session) and "the current year" (flagged). Bound as a scoped singleton. */
class Years
{
    private ?AcademicYear $current = null;
    private ?AcademicYear $selected = null;
    private mixed $selectedKey = null;

    public function current(): ?AcademicYear
    {
        return $this->current ??= AcademicYear::where('is_current', true)->first()
            ?? AcademicYear::orderByDesc('starts_on')->first();
    }

    public function selected(): ?AcademicYear
    {
        $id = session('year_id');
        // Recompute when the session's choice changed (several requests can share one container, e.g. in tests).
        if ($this->selected && $this->selectedKey === $id) {
            return $this->selected;
        }
        $this->selectedKey = $id;

        return $this->selected = ($id ? AcademicYear::find($id) : null) ?? $this->current();
    }

    public function isViewingCurrent(): bool
    {
        return $this->selected()?->id === $this->current()?->id;
    }

    public function forget(): void
    {
        $this->current = $this->selected = null;
        $this->selectedKey = null;
    }
}
