<?php

namespace App\Livewire\Categories;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categories Management')]
class Index extends Component
{
    public function render()
    {
        return view('components.categories.index');
    }
}
