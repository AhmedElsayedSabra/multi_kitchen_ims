<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.app')]
#[Title('System Settings')]
class SettingsIndex extends Component
{
    public $activeTab = 'general';
    public $settings = [];
    public $logoPreview = null;
    public $logoFile = null;
    public $groups = [];

    public function mount(): void
    {
        $this->loadSettings();
        $this->groups = SystemSetting::select('group')->distinct()->pluck('group')->toArray();
    }

    public function loadSettings(): void
    {
        $allSettings = SystemSetting::orderBy('group')->orderBy('label')->get();
        foreach ($allSettings as $setting) {
            $this->settings[$setting->key] = $setting->value;
        }
    }

    public function saveSettings(): void
    {
        foreach ($this->settings as $key => $value) {
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
            }
        }

        if ($this->logoFile) {
            $path = $this->logoFile->store('settings', 'public');
            $logoSetting = SystemSetting::where('key', 'company_logo')->first();
            if ($logoSetting) {
                if ($logoSetting->value) {
                    Storage::delete($logoSetting->value);
                }
                $logoSetting->update(['value' => $path]);
            }
            $this->settings['company_logo'] = $path;
        }

        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Settings Saved',
            'message' => 'System settings have been updated successfully.',
        ]);
    }

    public function updatedLogoFile(): void
    {
        if ($this->logoFile) {
            $this->logoPreview = $this->logoFile->temporaryUrl();
        }
    }

    public function removeLogo(): void
    {
        $setting = SystemSetting::where('key', 'company_logo')->first();
        if ($setting && $setting->value) {
            Storage::delete($setting->value);
            $setting->update(['value' => null]);
            $this->settings['company_logo'] = null;
            $this->logoPreview = null;
            $this->logoFile = null;
            $this->dispatch('toast-add', [
                'type' => 'success',
                'title' => 'Logo Removed',
                'message' => 'Company logo has been removed.',
            ]);
        }
    }

    public function render()
    {
        return view('components.settings.index', [
            'groups' => $this->groups,
        ]);
    }
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">System Settings</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Configure system-wide settings and preferences</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="border-b border-slate-200 dark:border-slate-700">
            <nav class="flex -mb-px" aria-label="Tabs">
                @foreach($groups as $group)
                <button
                    wire:click="activeTab = '{{ $group }}'"
                    class="px-4 py-3 text-sm font-medium text-center whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $group ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300' }}"
                >
                    {{ ucfirst($group) }}
                </button>
                @endforeach
            </nav>
        </div>

        <form wire:submit.prevent="saveSettings" class="p-6 space-y-6">
            @php
                $groupSettings = SystemSetting::where('group', $activeTab)->orderBy('label')->get();
            @endphp

            @foreach($groupSettings as $setting)
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $setting->label }}</label>
                @if($setting->description)
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $setting->description }}</p>
                @endif

                @switch($setting->type)
                    @case('string')
                    <x-input wire:model="settings.{{ $setting->key }}" placeholder="{{ $setting->label }}" />
                    @break

                    @case('text')
                    <x-textarea wire:model="settings.{{ $setting->key }}" rows="3" placeholder="{{ $setting->label }}" />
                    @break

                    @case('number')
                    <x-input wire:model="settings.{{ $setting->key }}" type="number" step="any" />
                    @break

                    @case('boolean')
                    <x-checkbox wire:model="settings.{{ $setting->key }}" label="Enabled" />
                    @break

                    @case('select')
                    @php $options = $setting->options ?? []; @endphp
                    <x-plain-select wire:model.live="settings.{{ $setting->key }}" :options="$options" searchable />
                    @break

                    @case('color')
                    <div class="flex items-center space-x-4">
                        <x-input wire:model="settings.{{ $setting->key }}" type="color" class="w-12 h-10 p-0 border-0" />
                        <x-input wire:model="settings.{{ $setting->key }}" placeholder="#RRGGBB" class="w-32 font-mono text-sm" />
                    </div>
                    @break

                    @case('image')
                    @if($setting->key === 'company_logo')
                    <div>
                        @if($settings[$setting->key] || $logoPreview)
                        <div class="mb-2">
                            <img src="{{ $logoPreview ?? Storage::url($settings[$setting->key]) }}" alt="Logo Preview" class="h-20 w-auto rounded border border-slate-200 dark:border-slate-700">
                            @if($settings[$setting->key])
                            <button type="button" wire:click="removeLogo" class="mt-1 text-sm text-rose-600 hover:text-rose-700">Remove Logo</button>
                            @endif
                        </div>
                        @endif
                        <x-input wire:model="logoFile" type="file" accept="image/*" />
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Recommended: 200x60px, PNG or SVG</p>
                    </div>
                    @endif
                    @break
                @endswitch
            </div>
            @endforeach

            <div class="flex justify-end pt-4 border-t border-slate-200 dark:border-slate-700">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>







