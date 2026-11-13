<?php

namespace App\Livewire\Users;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('User Management')]
class Index extends Component
{
    public function render()
    {
        return view('components.users.index');
    }
}
