<?php

namespace App\Livewire\StockControl;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Stock Control')]
class Index extends Component
{
    public function render()
    {
        return view('components.stock-control.index');
    }
}
