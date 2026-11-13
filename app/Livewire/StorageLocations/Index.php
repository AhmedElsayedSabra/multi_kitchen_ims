<?php

namespace App\Livewire\StorageLocations;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Storage Locations')]
class Index extends Component
{
    public function render()
    {
        return view('components.storage-locations.index');
    }
}
