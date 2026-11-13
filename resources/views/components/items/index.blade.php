<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Item;
use App\Models\Category;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\StorageLocation;
use App\Services\QuantityFormatterService;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
#[Title('Items Management')]
class ItemsIndex extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $stockFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showViewModal = false;
    public $selectedItem = null;

    public $name = '';
    public $sku = '';
    public $barcode = '';
    public $category_id = '';
    public $subcategory_id = '';
    public $unit_category_id = '';
    public $base_unit_id = '';
    public $default_purchase_unit_id = '';
    public $default_issue_unit_id = '';
    public $purchase_price = 0;
    public $cost_price = 0;
    public $min_level = 0;
    public $max_level = 0;
    public $reorder_point = 0;
    public $default_storage_location_id = '';
    public $track_expiry = true;
    public $is_active = true;
    public $description = '';
    public $image = '';
    public $imagePreview = null;
    public $skuManuallyEdited = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'sku' => 'required|string|max:50|unique:items,sku',
        'barcode' => 'nullable|string|max:50|unique:items,barcode',
        'category_id' => 'required|exists:categories,id',
        'subcategory_id' => 'nullable|exists:categories,id',
        'unit_category_id' => 'required|exists:unit_categories,id',
        'base_unit_id' => 'required|exists:units,id',
        'default_purchase_unit_id' => 'nullable|exists:units,id',
        'default_issue_unit_id' => 'nullable|exists:units,id',
        'purchase_price' => 'required|numeric|min:0',
        'cost_price' => 'required|numeric|min:0',
        'min_level' => 'required|numeric|min:0',
        'max_level' => 'nullable|numeric|min:0',
        'reorder_point' => 'required|numeric|min:0',
        'default_storage_location_id' => 'nullable|exists:storage_locations,id',
        'track_expiry' => 'boolean',
        'is_active' => 'boolean',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,jfif|max:2048',
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
        $this->category_id = Category::active()->whereNull('parent_id')->value('id') ?? '';
        $this->unit_category_id = UnitCategory::active()
            ->whereHas('units', fn ($query) => $query->where('is_base_unit', true)->where('status', 'active'))
            ->value('id') ?? '';
        $this->updatedUnitCategoryId();
        $this->default_storage_location_id = StorageLocation::active()->value('id') ?? '';
        $this->showCreateModal = true;
    }

    public function openEditModal(Item $item): void
    {
        $this->selectedItem = $item;
        $this->name = $item->name;
        $this->sku = $item->sku;
        $this->skuManuallyEdited = true;
        $this->barcode = $item->barcode;
        $this->category_id = $item->category_id;
        $this->subcategory_id = $item->subcategory_id;
        $this->unit_category_id = $item->unit_category_id;
        $this->base_unit_id = $item->base_unit_id;
        $this->default_purchase_unit_id = $item->default_purchase_unit_id;
        $this->default_issue_unit_id = $item->default_issue_unit_id;
        $this->purchase_price = $item->purchase_price;
        $this->cost_price = $item->cost_price;
        $this->min_level = $item->min_level;
        $this->max_level = $item->max_level;
        $this->reorder_point = $item->reorder_point;
        $this->default_storage_location_id = $item->default_storage_location_id;
        $this->track_expiry = $item->track_expiry;
        $this->is_active = $item->is_active;
        $this->description = $item->description;
        $this->imagePreview = $item->image ? Storage::url($item->image) : null;
        $this->showEditModal = true;
    }

    public function openViewModal(Item $item): void
    {
        $this->selectedItem = $item;
        $this->showViewModal = true;
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->sku = '';
        $this->skuManuallyEdited = false;
        $this->barcode = '';
        $this->category_id = '';
        $this->subcategory_id = '';
        $this->unit_category_id = '';
        $this->base_unit_id = '';
        $this->default_purchase_unit_id = '';
        $this->default_issue_unit_id = '';
        $this->purchase_price = 0;
        $this->cost_price = 0;
        $this->min_level = 0;
        $this->max_level = 0;
        $this->reorder_point = 0;
        $this->default_storage_location_id = '';
        $this->track_expiry = true;
        $this->is_active = true;
        $this->description = '';
        $this->image = '';
        $this->imagePreview = null;
        $this->selectedItem = null;
        $this->resetValidation();
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function updatedCategoryId(): void
    {
        $this->subcategory_id = '';
    }

    public function updatedUnitCategoryId(): void
    {
        $baseUnitId = Unit::query()
            ->where('unit_category_id', $this->unit_category_id)
            ->where('is_base_unit', true)
            ->where('status', 'active')
            ->value('id');

        $this->base_unit_id = $baseUnitId ?? '';
        $this->default_purchase_unit_id = $baseUnitId ?? '';
        $this->default_issue_unit_id = $baseUnitId ?? '';
    }

    public function updatedName(): void
    {
        if (!$this->skuManuallyEdited) {
            $this->generateSkuFromName();
        }
    }

    public function updatedSku(): void
    {
        $this->skuManuallyEdited = true;
    }

    private function generateSkuFromName(): void
    {
        $baseSku = Str::upper(Str::limit(Str::slug($this->name), 50, ''));

        if ($baseSku === '') {
            $baseSku = 'ITEM';
        }

        $sku = $baseSku;
        $suffix = 2;
        $query = Item::query()->where('sku', $sku);

        if ($this->selectedItem) {
            $query->whereKeyNot($this->selectedItem->id);
        }

        while ($query->exists()) {
            $suffixString = '-' . $suffix++;
            $sku = Str::limit($baseSku, 50 - strlen($suffixString), '') . $suffixString;
            $query = Item::query()->where('sku', $sku);

            if ($this->selectedItem) {
                $query->whereKeyNot($this->selectedItem->id);
            }
        }

        $this->sku = $sku;
    }

    public function generateBarcode(): void
    {
        $this->barcode = 'ITEM-' . str_pad(Item::max('id') + 1, 8, '0', STR_PAD_LEFT);
    }

    public function create(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode ?: null,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id ?: null,
            'unit_category_id' => $this->unit_category_id,
            'base_unit_id' => $this->base_unit_id,
            'default_purchase_unit_id' => $this->default_purchase_unit_id ?: null,
            'default_issue_unit_id' => $this->default_issue_unit_id ?: null,
            'purchase_price' => $this->purchase_price,
            'cost_price' => $this->cost_price,
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'reorder_point' => $this->reorder_point,
            'default_storage_location_id' => $this->default_storage_location_id ?: null,
            'track_expiry' => $this->track_expiry,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'created_by' => auth()->id(),
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('items', 'public');
        }

        Item::create($data);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Item Created',
            'message' => "Item {$this->name} has been created successfully.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:items,sku,' . $this->selectedItem->id,
            'barcode' => 'nullable|string|max:50|unique:items,barcode,' . $this->selectedItem->id,
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'unit_category_id' => 'required|exists:unit_categories,id',
            'base_unit_id' => 'required|exists:units,id',
            'default_purchase_unit_id' => 'nullable|exists:units,id',
            'default_issue_unit_id' => 'nullable|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'min_level' => 'required|numeric|min:0',
            'max_level' => 'nullable|numeric|min:0',
            'reorder_point' => 'required|numeric|min:0',
            'default_storage_location_id' => 'nullable|exists:storage_locations,id',
            'track_expiry' => 'boolean',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,jfif|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode ?: null,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id ?: null,
            'unit_category_id' => $this->unit_category_id,
            'base_unit_id' => $this->base_unit_id,
            'default_purchase_unit_id' => $this->default_purchase_unit_id ?: null,
            'default_issue_unit_id' => $this->default_issue_unit_id ?: null,
            'purchase_price' => $this->purchase_price,
            'cost_price' => $this->cost_price,
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'reorder_point' => $this->reorder_point,
            'default_storage_location_id' => $this->default_storage_location_id ?: null,
            'track_expiry' => $this->track_expiry,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'updated_by' => auth()->id(),
        ];

        if ($this->image) {
            if ($this->selectedItem->image) {
                Storage::delete($this->selectedItem->image);
            }
            $data['image'] = $this->image->store('items', 'public');
        }

        $this->selectedItem->update($data);

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Item Updated',
            'message' => "Item {$this->name} has been updated successfully.",
        ]);
    }

    public function confirmDelete(Item $item): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Item',
            'message' => "Are you sure you want to delete {$item->name}? This action cannot be undone.",
            'callback' => "delete({$item->id})",
        ]);
    }

    public function delete(Item $item): void
    {
        $name = $item->name;
        $item->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Item Deleted',
            'message' => "Item {$name} has been deleted.",
        ]);
    }

    public function toggleStatus(Item $item): void
    {
        $item->update(['is_active' => !$item->is_active]);
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Status Updated',
            'message' => "Item {$item->name} is now " . ($item->is_active ? 'active' : 'inactive') . ".",
        ]);
    }

    public function getSubcategoriesProperty()
    {
        if (!$this->category_id) return collect();
        return Category::active()->where('parent_id', $this->category_id)->get();
    }

    public function getUnitsProperty()
    {
        if (!$this->unit_category_id) return collect();
        return Unit::active()->where('unit_category_id', $this->unit_category_id)->get();
    }

    public function getItemsProperty()
    {
        $formatter = new QuantityFormatterService();

        return Item::with([
            'category', 'subcategory', 'baseUnit', 'purchaseUnit', 'issueUnit',
            'defaultStorageLocation', 'activeBatches'
        ])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orWhere('barcode', 'like', "%{$this->search}%"))
            ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
            ->when($this->statusFilter, fn($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->when($this->stockFilter, function($q) {
                $status = $this->stockFilter;
                $q->get()->filter(function($item) use ($status) {
                    return $item->stock_status['status'] === $status;
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.items.index', [
            'items' => $this->items,
            'categories' => Category::active()->whereNull('parent_id')->get(),
            'unitCategories' => \App\Models\UnitCategory::active()->get(),
            'storageLocations' => \App\Models\StorageLocation::active()->get(),
        ]);
    }
}
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Items Management</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage inventory items with units, pricing, and stock levels</p>
        </div>
        <button wire:click="openCreateModal" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
            <i class="fas fa-plus"></i>
            <span>Add Item</span>
        </button>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search items (name, SKU, barcode)..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="categoryFilter" :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->toArray()" placeholder="Filter by Category" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive']" placeholder="Filter by Status" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="stockFilter" :options="['' => 'All Stock Levels', 'out_of_stock' => 'Out of Stock', 'below_min' => 'Below Min', 'needs_reorder' => 'Needs Reorder', 'optimal' => 'Optimal', 'overstocked' => 'Overstocked']" placeholder="Stock Status" />
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">SKU</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Name <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Base Unit</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Current Stock</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Min/Reorder</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($items as $item)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ $item->sku }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                @if($item->image)
                                <img src="{{ Storage::url($item->image) }}" alt="" class="w-8 h-8 rounded object-cover">
                                @else
                                <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-box text-slate-400 dark:text-slate-500"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $item->name }}</p>
                                    @if($item->barcode)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ $item->barcode }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            {{ $item->category->name }}
                            @if($item->subcategory)
                            <span class="text-slate-400 dark:text-slate-500"> > </span>
                            {{ $item->subcategory->name }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->baseUnit->name }} ({{ $item->baseUnit->symbol }})</td>
                        <td class="px-4 py-3">
                            <div class="font-mono text-slate-900 dark:text-white">{{ number_format($item->current_stock, 2) }} {{ $item->baseUnit->symbol }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ app(\App\Services\QuantityFormatterService::class)->formatQuantity($item, $item->current_stock) }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            Min: {{ number_format($item->min_level, 2) }} {{ $item->baseUnit->symbol }}<br>
                            Reorder: {{ number_format($item->reorder_point, 2) }} {{ $item->baseUnit->symbol }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @php
                                    $status = $item->stock_status['status'];
                                    echo match($status) {
                                        'out_of_stock' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                        'below_min' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        'needs_reorder' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                        'optimal' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        'overstocked' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                                        default => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                    };
                                @endphp
                            ">{{ $item->stock_status['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:click="openViewModal({{ $item->id }})" class="p-2 text-slate-500 hover:text-sky-600 dark:hover:text-sky-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button wire:click="openEditModal({{ $item->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="confirmDelete({{ $item->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($items->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No items found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $items->links() }}
        </div>
    </div>

    {{-- Create Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add New Item</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="create" class="p-6 space-y-4" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Item Name" wire:model="name" placeholder="Tomatoes" :error="$errors->first('name')" />
                        <x-input label="SKU" wire:model="sku" placeholder="TOM-001" :error="$errors->first('sku')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Barcode" wire:model="barcode" placeholder="Auto-generate or scan" />
                        <button type="button" wire:click="generateBarcode" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center space-x-2">
                            <i class="fas fa-barcode"></i>
                            <span>Generate Barcode</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Category" wire:model.live="category_id" :options="$categories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('category_id')" />
                        <x-plain-select label="Subcategory" wire:model.live="subcategory_id" :options="['' => 'None'] + $subcategories->pluck('name', 'id')->toArray()" searchable placeholder="Select subcategory" />
                    </div>
                    <x-plain-select label="Unit Category" wire:model.live="unit_category_id" :options="$unitCategories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('unit_category_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-plain-select label="Base Unit" wire:model.live="base_unit_id" :options="$units->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('base_unit_id')" />
                            <p class="mt-1 text-xs text-slate-500">The smallest unit used to track this item's stock.</p>
                        </div>
                        <x-plain-select label="Purchase Unit" wire:model.live="default_purchase_unit_id" :options="['' => 'Same as Base'] + $units->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                        <x-plain-select label="Issue Unit" wire:model.live="default_issue_unit_id" :options="['' => 'Same as Base'] + $units->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Purchase Price" wire:model="purchase_price" type="number" step="0.01" min="0" placeholder="0.00" :error="$errors->first('purchase_price')" />
                        <x-input label="Cost Price" wire:model="cost_price" type="number" step="0.01" min="0" placeholder="0.00" :error="$errors->first('cost_price')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-input label="Min Level" wire:model="min_level" type="number" step="0.01" min="0" placeholder="0" :error="$errors->first('min_level')" />
                            <p class="mt-1 text-xs text-slate-500">The minimum quantity to keep in stock.</p>
                        </div>
                        <div>
                            <x-input label="Max Level" wire:model="max_level" type="number" step="0.01" min="0" placeholder="0 (no limit)" />
                            <p class="mt-1 text-xs text-slate-500">The desired upper stock limit; leave 0 for no limit.</p>
                        </div>
                        <div>
                            <x-input label="Reorder Point" wire:model="reorder_point" type="number" step="0.01" min="0" placeholder="0" :error="$errors->first('reorder_point')" />
                            <p class="mt-1 text-xs text-slate-500">When stock reaches this amount, plan a reorder.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Default Storage Location" wire:model.live="default_storage_location_id" :options="['' => 'None'] + $storageLocations->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                        <div class="flex items-center space-x-4">
                            <x-checkbox wire:model="track_expiry" label="Track Expiry" />
                            <x-checkbox wire:model="is_active" label="Active" />
                        </div>
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" placeholder="Optional description..." />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Image" wire:model="image" type="file" accept=".jfif,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
                        @if($imagePreview)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Preview</label>
                            <img src="{{ $imagePreview }}" alt="Preview" class="h-20 w-auto rounded border border-slate-200 dark:border-slate-700">
                        </div>
                        @endif
                    </div>
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Item</button>
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
            <div x-show="true" x-transition class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Item</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="update" class="p-6 space-y-4" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Item Name" wire:model="name" :error="$errors->first('name')" />
                        <x-input label="SKU" wire:model="sku" :error="$errors->first('sku')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Barcode" wire:model="barcode" :error="$errors->first('barcode')" />
                        <button type="button" wire:click="generateBarcode" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center space-x-2">
                            <i class="fas fa-barcode"></i>
                            <span>Generate Barcode</span>
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Category" wire:model.live="category_id" :options="$categories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('category_id')" />
                        <x-plain-select label="Subcategory" wire:model.live="subcategory_id" :options="['' => 'None'] + $subcategories->pluck('name', 'id')->toArray()" searchable placeholder="Select subcategory" />
                    </div>
                    <x-plain-select label="Unit Category" wire:model.live="unit_category_id" :options="$unitCategories->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('unit_category_id')" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-plain-select label="Base Unit" wire:model.live="base_unit_id" :options="$units->pluck('name', 'id')->toArray()" searchable required :error="$errors->first('base_unit_id')" />
                            <p class="mt-1 text-xs text-slate-500">The smallest unit used to track this item's stock.</p>
                        </div>
                        <x-plain-select label="Purchase Unit" wire:model.live="default_purchase_unit_id" :options="['' => 'Same as Base'] + $units->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                        <x-plain-select label="Issue Unit" wire:model.live="default_issue_unit_id" :options="['' => 'Same as Base'] + $units->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-input label="Purchase Price" wire:model="purchase_price" type="number" step="0.01" min="0" :error="$errors->first('purchase_price')" />
                        <x-input label="Cost Price" wire:model="cost_price" type="number" step="0.01" min="0" :error="$errors->first('cost_price')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-input label="Min Level" wire:model="min_level" type="number" step="0.01" min="0" :error="$errors->first('min_level')" />
                            <p class="mt-1 text-xs text-slate-500">The minimum quantity to keep in stock.</p>
                        </div>
                        <div>
                            <x-input label="Max Level" wire:model="max_level" type="number" step="0.01" min="0" />
                            <p class="mt-1 text-xs text-slate-500">The desired upper stock limit; leave 0 for no limit.</p>
                        </div>
                        <div>
                            <x-input label="Reorder Point" wire:model="reorder_point" type="number" step="0.01" min="0" :error="$errors->first('reorder_point')" />
                            <p class="mt-1 text-xs text-slate-500">When stock reaches this amount, plan a reorder.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Default Storage Location" wire:model.live="default_storage_location_id" :options="['' => 'None'] + $storageLocations->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                        <div class="flex items-center space-x-4">
                            <x-checkbox wire:model="track_expiry" label="Track Expiry" />
                            <x-checkbox wire:model="is_active" label="Active" />
                        </div>
                    </div>
                    <x-textarea label="Description" wire:model="description" rows="3" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-input label="Image" wire:model="image" type="file" accept=".jfif,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" />
                        @if($imagePreview)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current Image</label>
                            <img src="{{ $imagePreview }}" alt="Preview" class="h-20 w-auto rounded border border-slate-200 dark:border-slate-700">
                        </div>
                        @endif
                    </div>
                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $selectedItem)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $selectedItem->name }}</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">SKU</p>
                            <p class="font-mono font-medium text-slate-900 dark:text-white">{{ $selectedItem->sku }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Barcode</p>
                            <p class="font-mono font-medium text-slate-900 dark:text-white">{{ $selectedItem->barcode ?? '—' }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Category</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $selectedItem->category->name }}{{ $selectedItem->subcategory ? ' > ' . $selectedItem->subcategory->name : '' }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Base Unit</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $selectedItem->baseUnit->name }} ({{ $selectedItem->baseUnit->symbol }})</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Current Stock</p>
                            <p class="font-mono text-xl font-bold text-slate-900 dark:text-white">{{ number_format($selectedItem->current_stock, 2) }} {{ $selectedItem->baseUnit->symbol }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Stock Status</p>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @switch($selectedItem->stock_status['status'])
                                    @case('out_of_stock') bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 @break
                                    @case('below_min') bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 @break
                                    @case('needs_reorder') bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 @break
                                    @case('optimal') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 @break
                                    @case('overstocked') bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400 @break
                                    @default bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300
                                @endswitch
                            ">{{ $selectedItem->stock_status['label'] }}</span>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Cost Price</p>
                            <p class="font-mono font-medium text-slate-900 dark:text-white">${{ number_format($selectedItem->cost_price, 2) }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Total Value</p>
                            <p class="font-mono font-medium text-slate-900 dark:text-white">${{ number_format($selectedItem->current_stock * $selectedItem->cost_price, 2) }}</p>
                        </div>
                    </div>

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Stock Levels</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                            <div><span class="text-slate-500 dark:text-slate-400">Min Level:</span> <span class="font-medium">{{ number_format($selectedItem->min_level, 2) }} {{ $selectedItem->baseUnit->symbol }}</span></div>
                            <div><span class="text-slate-500 dark:text-slate-400">Max Level:</span> <span class="font-medium">{{ $selectedItem->max_level > 0 ? number_format($selectedItem->max_level, 2) . ' ' . $selectedItem->baseUnit->symbol : 'Unlimited' }}</span></div>
                            <div><span class="text-slate-500 dark:text-slate-400">Reorder Point:</span> <span class="font-medium">{{ number_format($selectedItem->reorder_point, 2) }} {{ $selectedItem->baseUnit->symbol }}</span></div>
                            <div><span class="text-slate-500 dark:text-slate-400">Suggested Order:</span> <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ number_format(max(0, $selectedItem->max_level - $selectedItem->current_stock), 2) }} {{ $selectedItem->baseUnit->symbol }}</span></div>
                        </div>
                    </div>

                    @if($selectedItem->activeBatches->count() > 0)
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Active Batches</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                        <th class="pb-2">Batch #</th>
                                        <th class="pb-2">Qty</th>
                                        <th class="pb-2">Expiry</th>
                                        <th class="pb-2">Days Left</th>
                                        <th class="pb-2">Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedItem->activeBatches as $batch)
                                    <tr class="border-b border-slate-100 dark:border-slate-800">
                                        <td class="py-2 font-mono">{{ $batch->batch_number }}</td>
                                        <td class="py-2">{{ number_format($batch->quantity_remaining, 2) }} {{ $selectedItem->baseUnit->symbol }}</td>
                                        <td class="py-2">{{ $batch->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="py-2">
                                            @if($batch->days_until_expiry !== null)
                                            <span class="{{ $batch->days_until_expiry <= 0 ? 'text-rose-600' : ($batch->days_until_expiry <= 7 ? 'text-amber-600' : 'text-emerald-600') }}">
                                                {{ $batch->days_until_expiry }} days
                                            </span>
                                            @else
                                            —
                                            @endif
                                        </td>
                                        <td class="py-2">{{ $batch->storageLocation->name ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>



