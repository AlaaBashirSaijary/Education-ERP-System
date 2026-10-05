<?php

namespace App\Livewire;

use App\Services\ReportService;
use Livewire\Component;

class FinanceReport extends Component
{
    public function render(ReportService $reports)
    {
        $year = app(\App\Support\Years::class)->selected();

        return view('livewire.finance-report', [
            'year' => $year,
            'rows' => $reports->financeByClass($year?->id),
            'monthly' => $reports->collectionsByMonth(6),
        ])->title(__('Financial report'));
    }
}
