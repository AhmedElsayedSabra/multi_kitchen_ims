<?php

namespace App\Livewire\Warehouses;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Warehouses')]
class Index extends Component
{
    public function render()
    {
        return view('components.warehouses.index');
    }
}
