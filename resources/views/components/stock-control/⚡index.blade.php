<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Kitchen;
use App\Models\StorageLocation;

new #[Layout('layouts.app')] #[Title('Stock Control')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouseFilter = '';
    public $kitchenFilter = '';
    public $locationFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showOnlyReorder = false;

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleReorderFilter(): void
    {
        $this->showOnlyReorder = !$this->showOnlyReorder;
        $this->resetPage();
    }

    public function getItemsProperty()
    {
        $query = Item::with([
            'category', 'baseUnit', 'defaultStorageLocation.warehouse',
            'activeBatches.storageLocation.warehouse'
        ])->active();

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orWhere('barcode', 'like', "%{$this->search}%");
        }

        if ($this->warehouseFilter) {
            $query->whereHas('defaultStorageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter))
                ->orWhereHas('activeBatches.storageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->kitchenFilter) {
            $query->whereHas('activeBatches.storageLocation.warehouse', fn($q) => $q->where('id', $this->kitchenFilter));
        }

        if ($this->locationFilter) {
            $query->whereHas('activeBatches.storageLocation', fn($q) => $q->where('id', $this->locationFilter));
        }

        $items = $query->orderBy($this->sortField, $this->sortDirection)->get();

        // Filter by stock status
        $items = $items->filter(function ($item) {
            if ($this->statusFilter) {
                return $item->stock_status['status'] === $this->statusFilter;
            }
            if ($this->showOnlyReorder) {
                return in_array($item->stock_status['status'], ['below_min', 'needs_reorder', 'out_of_stock']);
            }
            return true;
        });

        // Manual pagination
        $page = $this->page ?? 1;
        $perPage = $this->perPage;
        $total = $items->count();
        $items = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page, ['path' => request()->url()]
        );
    }

    public function getTotalValueProperty()
    {
        $items = Item::with(['activeBatches'])->active()->get();
        return $items->sum(fn($item) => $item->current_stock * $item->cost_price);
    }

    public function getTotalItemsProperty()
    {
        return Item::active()->count();
    }

    public function getLowStockCountProperty()
    {
        return Item::active()->get()->filter(fn($item) => in_array($item->stock_status['status'], ['below_min', 'needs_reorder', 'out_of_stock']))->count();
    }

    public function getOutOfStockCountProperty()
    {
        return Item::active()->get()->filter(fn($item) => $item->stock_status['status'] === 'out_of_stock')->count();
    }

    public function render()
    {
        return $this->view([
            'items' => $this->items,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'totalItems' => $this->totalItems,
            'lowStockCount' => $this->lowStockCount,
            'outOfStockCount' => $this->outOfStockCount,
            'totalValue' => $this->totalValue,
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Stock Control</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Monitor stock levels, reorder points, and inventory health</p>
        </div>
        <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" wire:model="showOnlyReorder" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Show only items needing reorder</span>
            </label>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total Items</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totalItems }}</p>
                </div>
                <div class="p-3 bg-sky-50 dark:bg-sky-900/30 rounded-xl">
                    <i class="fas fa-boxes text-2xl text-sky-600 dark:text-sky-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Low Stock Items</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-300">{{ $lowStockCount }}</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl">
                    <i class="fas fa-exclamation-triangle text-2xl text-amber-600 dark:text-amber-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Out of Stock</p>
                    <p class="text-3xl font-bold text-rose-600 dark:text-rose-300">{{ $outOfStockCount }}</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-xl">
                    <i class="fas fa-times-circle text-2xl text-rose-600 dark:text-rose-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total Inventory Value</p>
                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-300">${{ number_format($totalValue, 2) }}</p>
                </div>
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl">
                    <i class="fas fa-dollar-sign text-2xl text-emerald-600 dark:text-emerald-300"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search items (name, SKU, barcode)..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Filter by Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="kitchenFilter" :options="['' => 'All Kitchens'] + $kitchens->pluck('name', 'id')->toArray()" placeholder="Filter by Kitchen" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="locationFilter" :options="['' => 'All Locations'] + $storageLocations->pluck('name', 'id')->toArray()" placeholder="Filter by Location" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'out_of_stock' => 'Out of Stock', 'below_min' => 'Below Min', 'needs_reorder' => 'Needs Reorder', 'optimal' => 'Optimal', 'overstocked' => 'Overstocked']" placeholder="Stock Status" />
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
                            Item <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Current Stock</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Min / Reorder</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Suggested Order</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($items as $item)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30 {{ in_array($item->stock_status['status'], ['out_of_stock', 'below_min', 'needs_reorder']) ? 'bg-rose-50/30 dark:bg-rose-900/10' : '' }}">
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
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->category->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($item->defaultStorageLocation)
                            {{ $item->defaultStorageLocation->name }}
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">
                            {{ number_format($item->current_stock, 2) }} {{ $item->baseUnit->symbol }}
                        </td>
                        <td class="px-4 py-3 text-end text-sm text-slate-600 dark:text-slate-300">
                            Min: {{ number_format($item->min_level, 2) }} {{ $item->baseUnit->symbol }}<br>
                            Reorder: {{ number_format($item->reorder_point, 2) }} {{ $item->baseUnit->symbol }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            @if($item->current_stock < $item->reorder_point)
                            <span class="font-mono font-medium text-amber-600 dark:text-amber-300">
                                {{ number_format($item->max_level > 0 ? $item->max_level - $item->current_stock : $item->reorder_point - $item->current_stock, 2) }} {{ $item->baseUnit->symbol }}
                            </span>
                            @else
                            <span class="text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
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
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">
                            ${{ number_format($item->current_stock * $item->cost_price, 2) }}
                        </td>
                    </tr>
                    @endforeach
                    @if($items->isEmpty())
                    <tr>
                        <td colspan="9" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No items found</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $items->links() }}
        </div>
    </div>

    <!-- Reorder Summary -->
    @if($lowStockCount > 0)
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-100 mb-4 flex items-center space-x-2">
            <i class="fas fa-shopping-cart text-amber-600"></i>
            <span>Reorder Suggestions</span>
        </h3>
        <div class="space-y-2">
            @foreach($items->filter(fn($i) => in_array($i->stock_status['status'], ['out_of_stock', 'below_min', 'needs_reorder'])) as $item)
            <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="flex items-center space-x-3">
                    @if($item->image)
                    <img src="{{ Storage::url($item->image) }}" alt="" class="w-10 h-10 rounded object-cover">
                    @else
                    <div class="w-10 h-10 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        <i class="fas fa-box text-slate-400 dark:text-slate-500"></i>
                    </div>
                    @endif
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $item->name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">SKU: {{ $item->sku }} | {{ $item->category->name }}</p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="text-sm text-slate-500 dark:text-slate-400">Current: {{ number_format($item->current_stock, 2) }} {{ $item->baseUnit->symbol }}</p>
                    <p class="font-semibold text-amber-600 dark:text-amber-300">
                        Suggested: {{ number_format($item->max_level > 0 ? $item->max_level - $item->current_stock : $item->reorder_point - $item->current_stock, 2) }} {{ $item->baseUnit->symbol }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>






