<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Kitchen;

new #[Layout('layouts.app')] #[Title('Kitchens')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedKitchen = null;

    public $name = '';
    public $code = '';
    public $description = '';
    public $address = '';
    public $contact_person = '';
    public $phone = '';
    public $email = '';
    public $status = 'active';

    protected $rules = [
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:20|unique:kitchens,code',
        'description' => 'nullable|string',
        'address' => 'nullable|string',
        'contact_person' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email|max:255',
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

    public function openEditModal(Kitchen $kitchen): void
    {
        $this->selectedKitchen = $kitchen;
        $this->name = $kitchen->name;
        $this->code = $kitchen->code;
        $this->description = $kitchen->notes;
        $this->address = $kitchen->address;
        $this->contact_person = $kitchen->manager_name;
        $this->phone = $kitchen->phone;
        $this->email = $kitchen->email;
        $this->status = $kitchen->status;
        $this->showEditModal = true;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->address = '';
        $this->contact_person = '';
        $this->phone = '';
        $this->email = '';
        $this->status = 'active';
        $this->selectedKitchen = null;
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

        Kitchen::create([
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'notes' => $this->description,
            'address' => $this->address,
            'manager_name' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Kitchen Created',
            'message' => "Kitchen {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:kitchens,code,' . $this->selectedKitchen->id,
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $this->selectedKitchen->update([
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'notes' => $this->description,
            'address' => $this->address,
            'manager_name' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'updated_by' => auth()->id(),
        ]);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Kitchen Updated',
            'message' => "Kitchen {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(Kitchen $kitchen): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Kitchen',
            'message' => "Are you sure you want to delete {$kitchen->name}? This action cannot be undone.",
            'callback' => "delete({$kitchen->id})",
        ]);
    }

    public function delete(Kitchen $kitchen): void
    {
        $name = $kitchen->name;
        $kitchen->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Kitchen Deleted',
            'message' => "Kitchen {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(Kitchen $kitchen): void
    {
        $kitchen->update(['status' => $kitchen->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Status Updated',
            'message' => "Kitchen {$kitchen->name} is now {$kitchen->status}.",
        ]);
    }

    public function getKitchensProperty()
    {
        return Kitchen::with(['creator', 'updater'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return $this->view([
            'kitchens' => $this->kitchens,
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Kitchens</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage production kitchens and cooking facilities</p>
        </div>
        <button wire:loading.attr="disabled" wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Kitchen</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search kitchens..." />
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
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('code')">
                            Code <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Name <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Contact</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($kitchens as $kitchen)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $kitchen->code }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $kitchen->name }}</p>
                            @if($kitchen->notes)
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $kitchen->notes }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($kitchen->manager_name)
                            <p>{{ $kitchen->manager_name }}</p>
                            @endif
                            @if($kitchen->phone)
                            <p>{{ $kitchen->phone }}</p>
                            @endif
                            @if($kitchen->email)
                            <p>{{ $kitchen->email }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <button wire:loading.attr="disabled" wire:click="toggleStatus({{ $kitchen->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $kitchen->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ ucfirst($kitchen->status) }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:loading.attr="disabled" wire:click="openEditModal({{ $kitchen->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:loading.attr="disabled" wire:click="confirmDelete({{ $kitchen->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($kitchens->isEmpty())
                    <tr>
                        <td colspan="5" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No kitchens found</p><button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"><span aria-hidden="true">+</span> Add New</button></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $kitchens->links() }}
        </div>
    </div>

    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add Kitchen</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" placeholder="Main Kitchen" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" placeholder="KIT001" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" placeholder="Optional description..." />
                    <x-textarea label="Address" wire:model="address" rows="2" placeholder="Full address..." />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Contact Person" wire:model="contact_person" placeholder="Chef Name" />
                        <x-input label="Phone" wire:model="phone" placeholder="+1-555-0200" />
                        <x-input label="Email" wire:model="email" type="email" placeholder="kitchen@company.com" />
                    </div>
                    <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Kitchen</button>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Kitchen</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" />
                    <x-textarea label="Address" wire:model="address" rows="2" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Contact Person" wire:model="contact_person" />
                        <x-input label="Phone" wire:model="phone" />
                        <x-input label="Email" wire:model="email" type="email" />
                    </div>
                    <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Kitchen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>






