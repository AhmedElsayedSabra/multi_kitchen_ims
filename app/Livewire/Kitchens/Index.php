<?php

namespace App\Livewire\Kitchens;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Kitchens')]
class Index extends Component
{
    public function render()
    {
        return view('components.kitchens.index');
    }
}
