<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\User;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app')] #[Title('User Management')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedUser = null;

    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $roles = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'roles' => 'required|array|min:1',
        'roles.*' => 'exists:roles,name',
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

    public function openEditModal(User $user): void
    {
        $this->selectedUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->roles = $user->getRoleNames()->toArray();
        $this->showEditModal = true;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->roles = [];
        $this->selectedUser = null;
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

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($this->roles);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'User Created',
            'message' => "User {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->selectedUser->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        $this->selectedUser->update($data);
        $this->selectedUser->syncRoles($this->roles);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'User Updated',
            'message' => "User {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(User $user): void
    {
        if ($user->id === auth()->id()) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'You cannot delete your own account.',
            ]);
            return;
        }

        $this->dispatch('confirm-modal', [
            'title' => 'Delete User',
            'message' => "Are you sure you want to delete {$user->name}? This action cannot be undone.",
            'callback' => "delete({$user->id})",
        ]);
    }

    public function delete(User $user): void
    {
        $name = $user->name;
        $user->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'User Deleted',
            'message' => "User {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(User $user): void
    {
        // We don't have a status field, but we could add one or use a different approach
        // For now, we'll just show a message
        $this->dispatch('toast-add', [
            'type' => 'info',
            'title' => 'Info',
            'message' => 'Status toggle not implemented. Consider adding an is_active field to users.',
        ]);
    }

    public function getUsersProperty()
    {
        return User::with('roles')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->roleFilter, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $this->roleFilter)))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return $this->view([
            'users' => $this->users,
            'roleOptions' => Role::orderBy('name')->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">User Management</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage system users and their roles</p>
        </div>
        <button wire:loading.attr="disabled" wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add User</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search users..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="roleFilter" :options="['' => 'All Roles'] + $roleOptions->pluck('name', 'name')->toArray()" placeholder="Filter by Role" searchable />
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
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">User</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('email')">
                            Email <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Roles</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Last Login</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Created</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <span class="text-indigo-700 dark:text-indigo-300 font-medium text-lg">
                                        {{ strtoupper($user->name[0]) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $user->name }}</p>
                                    @if($user->id === auth()->id())
                                    <span class="text-xs text-indigo-600 dark:text-indigo-400">(You)</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->getRoleNames() as $role)
                                <x-badge :color="['admin' => 'red', 'warehouse_manager' => 'blue', 'viewer' => 'gray'][$role] ?? 'gray'" class="text-xs">{{ ucfirst(str_replace('_', ' ', $role)) }}</x-badge>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                @if($user->id !== auth()->id())
                                <button wire:loading.attr="disabled" wire:click="openEditModal({{ $user->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:loading.attr="disabled" wire:click="confirmDelete({{ $user->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($users->isEmpty())
                    <tr>
                        <td colspan="6" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No users found</p><button type="button" wire:click="openCreateModal" wire:loading.attr="disabled" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"><span aria-hidden="true">+</span> Add New</button></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $users->links() }}
        </div>
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add User</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4">
                    <x-input label="Name" wire:model="name" placeholder="John Doe" :error="$errors->first('name')" />
                    <x-input label="Email" wire:model="email" type="email" placeholder="john@example.com" :error="$errors->first('email')" />
                    <x-input label="Password" wire:model="password" type="password" placeholder="••••••••" :error="$errors->first('password')" />
                    <x-input label="Confirm Password" wire:model="password_confirmation" type="password" placeholder="••••••••" :error="$errors->first('password_confirmation')" />

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Roles</label>
                        <div class="space-y-2">
                            @foreach($roleOptions as $role)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" wire:model="roles" value="{{ $role->name }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('roles')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit User</h3>
                    <button wire:loading.attr="disabled" wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4">
                    <x-input label="Name" wire:model="name" :error="$errors->first('name')" />
                    <x-input label="Email" wire:model="email" type="email" :error="$errors->first('email')" />
                    <x-input label="New Password (leave blank to keep current)" wire:model="password" type="password" placeholder="••••••••" :error="$errors->first('password')" />
                    <x-input label="Confirm New Password" wire:model="password_confirmation" type="password" placeholder="••••••••" :error="$errors->first('password_confirmation')" />

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Roles</label>
                        <div class="space-y-2">
                            @foreach($roleOptions as $role)
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" wire:model="roles" value="{{ $role->name }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('roles')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button wire:loading.attr="disabled" type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button wire:loading.attr="disabled" type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>





