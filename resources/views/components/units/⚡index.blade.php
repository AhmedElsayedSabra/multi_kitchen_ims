<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Unit;
use App\Models\UnitCategory;

new #[Layout('layouts.app')] #[Title('Units Management')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedUnit = null;

    public $unit_category_id = '';
    public $parent_unit_id = '';
    public $name = '';
    public $symbol = '';
    public $is_base_unit = false;
    public $conversion_factor = 1;
    public $sort_order = 0;
    public $status = 'active';
    public $notes = '';

    protected $rules = [
        'unit_category_id' => 'required|exists:unit_categories,id',
        'parent_unit_id' => 'nullable|exists:units,id',
        'name' => 'required|string|max:255',
        'symbol' => 'required|string|max:20',
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'required|numeric|min:0.000001',
        'sort_order' => 'nullable|integer|min:0',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ];

    public function mount(): void {}

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(Unit $unit): void
    {
        $this->selectedUnit = $unit;
        $this->unit_category_id = $unit->unit_category_id;
        $this->parent_unit_id = $unit->parent_unit_id;
        $this->name = $unit->name;
        $this->symbol = $unit->symbol;
        $this->is_base_unit = $unit->is_base_unit;
        $this->conversion_factor = $unit->conversion_factor;
        $this->sort_order = $unit->sort_order;
        $this->status = $unit->status;
        $this->notes = $unit->notes;
        $this->showEditModal = true;
    }

    public function resetForm(): void
    {
        $this->unit_category_id = '';
        $this->parent_unit_id = '';
        $this->name = '';
        $this->symbol = '';
        $this->is_base_unit = false;
        $this->conversion_factor = 1;
        $this->sort_order = 0;
        $this->status = 'active';
        $this->notes = '';
        $this->selectedUnit = null;
        $this->resetValidation();
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function updatedUnitCategoryId(): void
    {
        $this->parent_unit_id = '';
        $this->conversion_factor = 1;
        $this->is_base_unit = false;
    }

    public function create(): void
    {
        $this->validate([
            'unit_category_id' => 'required|exists:unit_categories,id',
            'parent_unit_id' => 'nullable|exists:units,id',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'is_base_unit' => 'boolean',
            'conversion_factor' => 'required|numeric|min:0.000001',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ], [], [
            'unit_category_id' => 'unit category',
            'parent_unit_id' => 'parent unit',
            'conversion_factor' => 'conversion factor',
            'sort_order' => 'sort order',
        ]);

        // Check for duplicate base unit in category
        if ($this->is_base_unit) {
            $existing = Unit::where('unit_category_id', $this->unit_category_id)
                ->where('is_base_unit', true)
                ->exists();
            if ($existing) {
                $this->addError('is_base_unit', 'A base unit already exists for this category.');
                return;
            }
            $this->conversion_factor = 1;
        }

        Unit::create([
            'unit_category_id' => $this->unit_category_id,
            'parent_unit_id' => $this->parent_unit_id ?: null,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_base_unit' => $this->is_base_unit,
            'conversion_factor' => $this->conversion_factor,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Unit Created',
            'message' => "Unit {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'unit_category_id' => 'required|exists:unit_categories,id',
            'parent_unit_id' => 'nullable|exists:units,id',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'is_base_unit' => 'boolean',
            'conversion_factor' => 'required|numeric|min:0.000001',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
        ], [], [
            'unit_category_id' => 'unit category',
            'parent_unit_id' => 'parent unit',
            'conversion_factor' => 'conversion factor',
            'sort_order' => 'sort order',
        ]);

        // Check for duplicate base unit in category (excluding current)
        if ($this->is_base_unit) {
            $existing = Unit::where('unit_category_id', $this->unit_category_id)
                ->where('is_base_unit', true)
                ->where('id', '!=', $this->selectedUnit->id)
                ->exists();
            if ($existing) {
                $this->addError('is_base_unit', 'A base unit already exists for this category.');
                return;
            }
            $this->conversion_factor = 1;
        }

        $this->selectedUnit->update([
            'unit_category_id' => $this->unit_category_id,
            'parent_unit_id' => $this->parent_unit_id ?: null,
            'name' => $this->name,
            'symbol' => $this->symbol,
            'is_base_unit' => $this->is_base_unit,
            'conversion_factor' => $this->conversion_factor,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'notes' => $this->notes,
            'updated_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Unit Updated',
            'message' => "Unit {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(Unit $unit): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Unit',
            'message' => "Are you sure you want to delete {$unit->name}? This action cannot be undone.",
            'callback' => "delete({$unit->id})",
        ]);
    }

    public function delete(Unit $unit): void
    {
        $name = $unit->name;
        $unit->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Unit Deleted',
            'message' => "Unit {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(Unit $unit): void
    {
        $unit->update(['status' => $unit->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Status Updated',
            'message' => "Unit {$unit->name} is now {$unit->status}.",
        ]);
    }

    public function getUnitsProperty()
    {
        return Unit::with(['unitCategory', 'parentUnit', 'childUnits', 'creator', 'updater'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('symbol', 'like', "%{$this->search}%")
                ->orWhereHas('unitCategory', fn($uc) => $uc->where('name', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter, fn($q) => $q->where('unit_category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy('unit_category_id')
            ->orderBy('is_base_unit', 'desc')
            ->orderBy('conversion_factor')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return $this->view([
            'units' => $this->units,
            'categories' => UnitCategory::active()->get(),
            'parentUnits' => Unit::active()->where('is_base_unit', false)->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Units Management</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage measurement units and conversion factors</p>
        </div>
        <button wire:loading.attr="disabled" wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Unit</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search units..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="categoryFilter" :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->toArray()" placeholder="Filter by Category" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive']" placeholder="Filter by Status" />
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Unit</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Symbol</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Conversion Factor</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Parent Unit</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($units as $unit)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $unit->unitCategory->name }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $unit->name }}</p>
                            @if($unit->notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $unit->notes }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-600 dark:text-slate-300">{{ $unit->symbol }}</td>
                        <td class="px-4 py-3">
                            @if($unit->is_base_unit)
                            <x-badge color="green">Base Unit</x-badge>
                            @elseif($unit->parent_unit_id)
                            <x-badge color="blue">Derived</x-badge>
                            @else
                            <x-badge color="gray">Independent</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">
                            @if($unit->is_base_unit)
                            1.000000 (Base)
                            @else
                            {{ number_format($unit->conversion_factor, 6) }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($unit->parentUnit)
                            {{ $unit->parentUnit->name }} ({{ $unit->parentUnit->symbol }})
                            @else
                            <span class="text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button wire:loading.attr="disabled" wire:click="toggleStatus({{ $unit->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $unit->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ ucfirst($unit->status) }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:loading.attr="disabled" wire:click="openEditModal({{ $unit->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:loading.attr="disabled" wire:click="confirmDelete({{ $unit->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($units->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No units found</p><button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"><span aria-hidden="true">+</span> Add New</button></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $units->links() }}
        </div>
    </div>

    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add Unit</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4">
                    <x-plain-select label="Unit Category" wire:model.live="unit_category_id" :options="$categories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('unit_category_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" placeholder="Kilogram" :error="$errors->first('name')" />
                        <x-input label="Symbol" wire:model="symbol" placeholder="kg" maxlength="20" :error="$errors->first('symbol')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-checkbox wire:model="is_base_unit" label="Base Unit" />
                        <x-input label="Conversion Factor" wire:model="conversion_factor" type="number" step="0.000001" min="0.000001" placeholder="1.000000" :error="$errors->first('conversion_factor')" />
                        <x-input label="Sort Order" wire:model="sort_order" type="number" min="0" placeholder="0" />
                    </div>
                    <x-plain-select label="Parent Unit (for nested packaging)" wire:model.live="parent_unit_id" :options="['' => 'None'] + $parentUnits->where('unit_category_id', $unit_category_id)->pluck('name', 'id')->toArray()" searchable placeholder="Select parent unit" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    </div>
                    <x-textarea label="Notes" wire:model="notes" rows="2" placeholder="Optional notes..." />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Unit</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4">
                    <x-plain-select label="Unit Category" wire:model.live="unit_category_id" :options="$categories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('unit_category_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" :error="$errors->first('name')" />
                        <x-input label="Symbol" wire:model="symbol" maxlength="20" :error="$errors->first('symbol')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-checkbox wire:model="is_base_unit" label="Base Unit" />
                        <x-input label="Conversion Factor" wire:model="conversion_factor" type="number" step="0.000001" min="0.000001" :error="$errors->first('conversion_factor')" />
                        <x-input label="Sort Order" wire:model="sort_order" type="number" min="0" />
                    </div>
                    <x-plain-select label="Parent Unit" wire:model.live="parent_unit_id" :options="['' => 'None'] + $parentUnits->where('unit_category_id', $unit_category_id)->pluck('name', 'id')->toArray()" searchable placeholder="Select parent unit" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    </div>
                    <x-textarea label="Notes" wire:model="notes" rows="2" />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Unit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>







