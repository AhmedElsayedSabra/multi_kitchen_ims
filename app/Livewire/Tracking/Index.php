<?php

namespace App\Livewire\Tracking;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Batch Tracking')]
class Index extends Component
{
    public function render()
    {
        return view('components.tracking.index');
    }
}
