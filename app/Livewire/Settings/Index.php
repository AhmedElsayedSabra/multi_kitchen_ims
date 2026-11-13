<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('System Settings')]
class Index extends Component
{
    public function render()
    {
        return view('components.settings.index');
    }
}
