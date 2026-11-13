<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\StorageLocation;
use App\Models\Warehouse;

new #[Layout('layouts.app')] #[Title('Storage Locations')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouseFilter = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedLocation = null;

    public $warehouse_id = '';
    public $name = '';
    public $code = '';
    public $type = '';
    public $description = '';
    public $capacity = '';
    public $status = 'active';

    protected $rules = [
        'warehouse_id' => 'required|exists:warehouses,id',
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:20|unique:storage_locations,code',
        'type' => 'nullable|string|max:50',
        'description' => 'nullable|string',
        'capacity' => 'nullable|integer|min:0',
        'status' => 'required|in:active,inactive',
    ];

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

    public function openEditModal(StorageLocation $location): void
    {
        $this->selectedLocation = $location;
        $this->warehouse_id = $location->warehouse_id;
        $this->name = $location->name;
        $this->code = $location->code;
        $this->type = $location->type;
        $this->description = $location->description;
        $this->capacity = $location->capacity;
        $this->status = $location->status;
        $this->showEditModal = true;
    }

    public function resetForm(): void
    {
        $this->warehouse_id = '';
        $this->name = '';
        $this->code = '';
        $this->type = '';
        $this->description = '';
        $this->capacity = '';
        $this->status = 'active';
        $this->selectedLocation = null;
        $this->resetValidation();
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function create(): void
    {
        $this->validate();

        StorageLocation::create([
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'description' => $this->description,
            'capacity' => $this->capacity ?: null,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Location Created',
            'message' => "Storage location {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:storage_locations,code,' . $this->selectedLocation->id,
            'type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $this->selectedLocation->update([
            'warehouse_id' => $this->warehouse_id,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'type' => $this->type,
            'description' => $this->description,
            'capacity' => $this->capacity ?: null,
            'status' => $this->status,
            'updated_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Location Updated',
            'message' => "Storage location {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(StorageLocation $location): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Storage Location',
            'message' => "Are you sure you want to delete {$location->name}? This action cannot be undone.",
            'callback' => "delete({$location->id})",
        ]);
    }

    public function delete(StorageLocation $location): void
    {
        $name = $location->name;
        $location->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Location Deleted',
            'message' => "Storage location {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(StorageLocation $location): void
    {
        $location->update(['status' => $location->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Status Updated',
            'message' => "Storage location {$location->name} is now {$location->status}.",
        ]);
    }

    public function getLocationsProperty()
    {
        return StorageLocation::with(['warehouse', 'creator', 'updater', 'itemBatches'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('type', 'like', "%{$this->search}%"))
            ->when($this->warehouseFilter, fn($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->typeFilter, fn($q) => $q->where('type', $this->typeFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return $this->view([
            'locations' => $this->locations,
            'warehouses' => Warehouse::active()->get(),
            'types' => StorageLocation::select('type')->distinct()->whereNotNull('type')->pluck('type')->toArray(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Storage Locations</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage physical storage locations within warehouses</p>
        </div>
        <button wire:loading.attr="disabled" wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Location</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search locations..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Filter by Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive']" placeholder="Filter by Status" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="typeFilter" :options="['' => 'All Types', 'cold' => 'Cold Room', 'dry' => 'Dry Store', 'freezer' => 'Freezer', 'shelf' => 'Shelf', 'other' => 'Other']" placeholder="Filter by Type" />
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Warehouse</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('code')">
                            Code <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Name <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Capacity</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Items/Batches</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($locations as $location)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $location->warehouse->name }}</td>
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $location->code }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $location->name }}</p>
                            @if($location->description)
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $location->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if($location->type)
                            <x-badge :color="['cold' => 'blue', 'freezer' => 'indigo', 'dry' => 'amber', 'shelf' => 'gray'][$location->type] ?? 'gray'">{{ ucfirst($location->type) }}</x-badge>
                            @else
                            <span class="text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($location->capacity)
                            {{ number_format($location->capacity) }}
                            @else
                            <span class="text-slate-400 dark:text-slate-500">Unlimited</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $location->itemBatches->count() }}</td>
                        <td class="px-4 py-3">
                            <button wire:loading.attr="disabled" wire:click="toggleStatus({{ $location->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $location->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ ucfirst($location->status) }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:loading.attr="disabled" wire:click="openEditModal({{ $location->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:loading.attr="disabled" wire:click="confirmDelete({{ $location->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($locations->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No storage locations found</p><button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"><span aria-hidden="true">+</span> Add New</button></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $locations->links() }}
        </div>
    </div>

    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add Storage Location</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4">
                    <x-plain-select label="Warehouse" wire:model.live="warehouse_id" :options="$warehouses->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('warehouse_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Location Name" wire:model="name" placeholder="Cold Room 1" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" placeholder="CR1" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Type" wire:model.live="type" :options="['cold' => 'Cold Room', 'dry' => 'Dry Store', 'freezer' => 'Freezer', 'shelf' => 'Shelf', 'other' => 'Other']" placeholder="Select type" />
                        <x-input label="Capacity" wire:model="capacity" type="number" min="0" placeholder="Optional" />
                    </div>
                    <x-input label="Description" wire:model="description" placeholder="Optional description" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    </div>
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Location</button>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Storage Location</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4">
                    <x-plain-select label="Warehouse" wire:model.live="warehouse_id" :options="$warehouses->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('warehouse_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Location Name" wire:model="name" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Type" wire:model.live="type" :options="['cold' => 'Cold Room', 'dry' => 'Dry Store', 'freezer' => 'Freezer', 'shelf' => 'Shelf', 'other' => 'Other']" placeholder="Select type" />
                        <x-input label="Capacity" wire:model="capacity" type="number" min="0" placeholder="Optional" />
                    </div>
                    <x-input label="Description" wire:model="description" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    </div>
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>







