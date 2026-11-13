<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Valuation Report')]
class Valuation extends Component
{
    public function render()
    {
        return view('components.reports.valuation');
    }
}
