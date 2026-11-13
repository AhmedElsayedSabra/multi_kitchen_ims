<?php

namespace App\Livewire\Reports;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Expiry Report')]
class Expiry extends Component
{
    public function render()
    {
        return view('components.reports.expiry');
    }
}
