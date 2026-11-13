<?php

namespace App\Livewire\Layouts;

use Livewire\Component;

class Topbar extends Component
{
    public ?string $title = null;

    public function render()
    {
        return view('livewire.layouts.topbar');
    }
}
