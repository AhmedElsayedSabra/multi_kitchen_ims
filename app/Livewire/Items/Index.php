<?php

namespace App\Livewire\Items;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Items Management')]
class Index extends Component
{
    public function render()
    {
        return view('components.items.index');
    }
}
