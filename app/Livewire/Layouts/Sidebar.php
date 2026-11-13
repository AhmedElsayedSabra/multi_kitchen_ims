<?php

namespace App\Livewire\Layouts;

use App\Models\SystemSetting;
use Livewire\Component;

class Sidebar extends Component
{
    public array $settings;

    public string $activeMenu = '';

    public function mount(): void
    {
        $this->settings = SystemSetting::getSettings();
    }

    public function render()
    {
        return view('livewire.layouts.sidebar');
    }
}
