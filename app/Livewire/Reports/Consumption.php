<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Consumption Report')]
class Consumption extends Component
{
    public function render()
    {
        return view('components.reports.consumption');
    }
}
