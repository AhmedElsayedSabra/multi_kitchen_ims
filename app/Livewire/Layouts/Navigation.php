<?php

namespace App\Livewire\Layouts;

use App\Models\SystemSetting;
use Livewire\Component;

class Navigation extends Component
{
    public array $settings;

    public function mount(): void
    {
        $this->settings = SystemSetting::getSettings();
    }

    public function render()
    {
        return view('livewire.layouts.navigation');
    }
}
