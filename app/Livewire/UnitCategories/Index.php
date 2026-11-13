<?php

namespace App\Livewire\UnitCategories;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Unit Categories')]
class Index extends Component
{
    public function render()
    {
        return view('components.unit-categories.index');
    }
}
