<?php

namespace App\Livewire;

use App\Services\ReportService;
use Livewire\Component;

class FinanceReport extends Component
{
    public function render(ReportService $reports)
    {
        return view('livewire.finance-report', [
            'rows' => $reports->financeByClass(),
            'monthly' => $reports->collectionsByMonth(6),
        ])->title(__('Financial report'));
    }
}
