<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Category;

#[Layout('layouts.app')]
#[Title('Categories Management')]
class CategoriesIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $parentFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedCategory = null;

    public $parent_id = '';
    public $name = '';
    public $code = '';
    public $description = '';
    public $image = '';
    public $sort_order = 0;
    public $status = 'active';

    protected $rules = [
        'parent_id' => 'nullable|exists:categories,id',
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:20|unique:categories,code',
        'description' => 'nullable|string',
        'image' => 'nullable|image|max:2048',
        'sort_order' => 'nullable|integer|min:0',
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

    public function openEditModal(Category $category): void
    {
        $this->selectedCategory = $category;
        $this->parent_id = $category->parent_id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->description = $category->description;
        $this->sort_order = $category->sort_order;
        $this->status = $category->status;
        $this->showEditModal = true;
    }

    public function resetForm(): void
    {
        $this->parent_id = '';
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->image = '';
        $this->sort_order = 0;
        $this->status = 'active';
        $this->selectedCategory = null;
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

        $data = [
            'parent_id' => $this->parent_id ?: null,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'created_by' => auth()->id(),
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('categories', 'public');
        }

        Category::create($data);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Category Created',
            'message' => "Category {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:categories,code,' . $this->selectedCategory->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data = [
            'parent_id' => $this->parent_id ?: null,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'updated_by' => auth()->id(),
        ];

        if ($this->image) {
            if ($this->selectedCategory->image) {
                Storage::delete($this->selectedCategory->image);
            }
            $data['image'] = $this->image->store('categories', 'public');
        }

        $this->selectedCategory->update($data);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Category Updated',
            'message' => "Category {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(Category $category): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Category',
            'message' => "Are you sure you want to delete {$category->name}? This action cannot be undone.",
            'callback' => "delete({$category->id})",
        ]);
    }

    public function delete(Category $category): void
    {
        $name = $category->name;
        $category->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Category Deleted',
            'message' => "Category {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(Category $category): void
    {
        $category->update(['status' => $category->status === 'active' ? 'inactive' : 'active']);
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Status Updated',
            'message' => "Category {$category->name} is now {$category->status}.",
        ]);
    }

    public function getCategoriesProperty()
    {
        return Category::with(['parent', 'children', 'items', 'creator', 'updater'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->parentFilter, fn($q) => $q->where('parent_id', $this->parentFilter))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.categories.index', [
            'categories' => $this->categories,
            'parentCategories' => Category::active()->whereNull('parent_id')->get(),
        ]);
    }
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Categories Management</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage product categories and subcategories</p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Category</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search categories..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive']" placeholder="Filter by Status" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="parentFilter" :options="['' => 'All (including subcategories)', 'root' => 'Root Categories Only'] + $parentCategories->pluck('name', 'id')->toArray()" placeholder="Filter by Parent" searchable />
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Name <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Parent</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Subcategories</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($categories as $category)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $category->code }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                @if($category->image)
                                <img src="{{ Storage::url($category->image) }}" alt="" class="w-8 h-8 rounded object-cover">
                                @else
                                <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-tag text-slate-400 dark:text-slate-500"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $category->name }}</p>
                                    @if($category->description)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $category->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($category->parent)
                            {{ $category->parent->name }}
                            @else
                            <span class="text-slate-400 dark:text-slate-500">— (Root)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $category->items->count() }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $category->children->count() }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="toggleStatus({{ $category->id }})" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $category->status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300' }}">
                                {{ ucfirst($category->status) }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:click="openEditModal({{ $category->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $category->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($categories->isEmpty())
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No categories found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $categories->links() }}
        </div>
    </div>

    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add Category</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4" enctype="multipart/form-data">
                    <x-plain-select label="Parent Category (Optional)" wire:model.live="parent_id" :options="['' => 'Root Category'] + $parentCategories->pluck('name', 'id')->toArray()" searchable placeholder="Select parent" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" placeholder="Vegetables" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" placeholder="VEG" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" placeholder="Optional description..." />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Image" wire:model="image" type="file" accept="image/*" />
                        <x-input label="Sort Order" wire:model="sort_order" type="number" min="0" placeholder="0" />
                    </div>
                    <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Category</button>
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
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Category</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4" enctype="multipart/form-data">
                    <x-plain-select label="Parent Category" wire:model.live="parent_id" :options="['' => 'Root Category'] + $parentCategories->where('id', '!=', $selectedCategory->id)->pluck('name', 'id')->toArray()" searchable placeholder="Select parent" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Name" wire:model="name" :error="$errors->first('name')" />
                        <x-input label="Code" wire:model="code" maxlength="20" :error="$errors->first('code')" />
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Image" wire:model="image" type="file" accept="image/*" />
                        <x-input label="Sort Order" wire:model="sort_order" type="number" min="0" />
                    </div>
                    @if($selectedCategory->image)
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current Image</label>
                        <img src="{{ Storage::url($selectedCategory->image) }}" alt="Current Image" class="h-16 w-auto rounded border border-slate-200 dark:border-slate-700">
                    </div>
                    @endif
                    <x-plain-select label="Status" wire:model.live="status" :options="['active' => 'Active', 'inactive' => 'Inactive']" />
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>







