<?php

namespace App\Livewire;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\SchoolClass;
use App\Services\PromotionService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Years extends Component
{
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $starts_on = '';
    public string $ends_on = '';

    // Promotion wizard
    public bool $showPromotion = false;
    public ?int $fromId = null;
    public ?int $toId = null;
    /** @var array<int,string> fromClassId => toClassId|'graduate' */
    public array $map = [];
    public bool $makeCurrent = true;
    public array $previewRows = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);
    }

    public function create(): void
    {
        $this->resetForm();
        $next = (AcademicYear::max('starts_on') ? \Illuminate\Support\Carbon::parse(AcademicYear::max('starts_on'))->addYear() : today());
        $this->starts_on = $next->format('Y').'-09-01';
        $this->ends_on = ($next->year + 1).'-06-30';
        $this->name = $next->year.'/'.($next->year + 1);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $y = AcademicYear::findOrFail($id);
        $this->editingId = $y->id;
        $this->name = $y->name;
        $this->starts_on = $y->starts_on->toDateString();
        $this->ends_on = $y->ends_on->toDateString();
        $this->resetValidation();
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset('editingId', 'name', 'starts_on', 'ends_on', 'showForm');
        $this->resetValidation();
    }

    public function save(): void
    {
        $d = $this->validate([
            'name' => ['required', 'string', 'max:30', Rule::unique('academic_years', 'name')->ignore($this->editingId)],
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
        ]);

        if ($this->editingId) {
            AcademicYear::findOrFail($this->editingId)->update($d);
        } else {
            AcademicYear::create($d)->createDefaultTerms();
        }
        $this->resetForm();
        session()->flash('ok', __('Saved.'));
    }

    public function makeCurrentYear(int $id): void
    {
        AcademicYear::findOrFail($id)->makeCurrent();
        session()->forget('year_id');
        session()->flash('ok', __('This is now the current academic year.'));
    }

    public function delete(int $id): void
    {
        $y = AcademicYear::findOrFail($id);
        if ($y->is_current || ! $y->isEmpty()) {
            session()->flash('warn', __('Only an empty, non-current year can be deleted.'));

            return;
        }
        $y->delete();
        session()->forget('year_id');
    }

    /* ---- Promotion wizard ---- */

    public function openPromotion(): void
    {
        $this->showPromotion = true;
        $this->fromId = AcademicYear::where('is_current', true)->value('id');
        $this->toId = AcademicYear::where('id', '!=', $this->fromId)->orderByDesc('starts_on')->value('id');
        $this->buildMap();
    }

    public function updatedFromId(): void
    {
        $this->buildMap();
    }

    /** Default mapping: next class (by creation order) within the same section; the last one graduates. */
    private function buildMap(): void
    {
        $this->previewRows = [];
        $used = Enrollment::where('academic_year_id', $this->fromId)->pluck('school_class_id')->unique();
        $classes = SchoolClass::orderBy('id')->get();
        $map = [];
        foreach ($classes->whereIn('id', $used) as $c) {
            $sameSection = $classes->where('section', $c->section)->values();
            $i = $sameSection->search(fn ($x) => $x->id === $c->id);
            $map[$c->id] = (string) ($sameSection[$i + 1]->id ?? PromotionService::GRADUATE);
        }
        $this->map = $map;
    }

    private function validated(): array
    {
        $this->validate([
            'fromId' => 'required|exists:academic_years,id',
            'toId' => 'required|exists:academic_years,id|different:fromId',
            'map.*' => ['required', fn ($a, $v, $fail) => $v === PromotionService::GRADUATE || SchoolClass::whereKey($v)->exists() ?: $fail(__('Choose a class.'))],
        ]);

        return [AcademicYear::findOrFail($this->fromId), AcademicYear::findOrFail($this->toId)];
    }

    public function preview(PromotionService $service): void
    {
        [$from, $to] = $this->validated();
        $this->previewRows = collect($service->preview($from, $to, $this->map))->map(fn ($r) => [
            'from' => $r['from']->name.' '.$r['from']->section, 'to' => $r['to'] ? $r['to']->name.' '.$r['to']->section : null,
            'graduate' => $r['graduate'], 'students' => $r['students'], 'skipped' => $r['skipped'],
        ])->all();
    }

    public function apply(PromotionService $service): void
    {
        [$from, $to] = $this->validated();
        $r = $service->apply($from, $to, $this->map, $this->makeCurrent);
        session()->forget('year_id');
        $this->showPromotion = false;
        $this->previewRows = [];
        session()->flash('ok', __('Promotion finished: :p promoted, :g graduated, :s skipped.', ['p' => $r['promoted'], 'g' => $r['graduated'], 's' => $r['skipped']]));
    }

    public function render()
    {
        $years = AcademicYear::with('terms')->withCount('enrollments')->orderByDesc('starts_on')->get()->each(function ($y) {
            $y->exams_count = Exam::where('academic_year_id', $y->id)->count();
            $y->fees_count = Fee::where('academic_year_id', $y->id)->count();
        });

        return view('livewire.years', [
            'years' => $years,
            'classes' => SchoolClass::orderBy('id')->get()->keyBy('id'),
        ])->title(__('Academic years'));
    }
}
