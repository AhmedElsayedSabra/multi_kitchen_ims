<?php

namespace App\Livewire\Units;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Units')]
class Index extends Component
{
    public function render()
    {
        return view('components.units.index');
    }
}
