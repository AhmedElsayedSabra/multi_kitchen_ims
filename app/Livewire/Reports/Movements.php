<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Movements Report')]
class Movements extends Component
{
    public function render()
    {
        return view('components.reports.movements');
    }
}
