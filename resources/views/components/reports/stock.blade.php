<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Kitchen;
use App\Models\StorageLocation;
use App\Models\Category;
use App\Models\ItemBatch;
use App\Services\QuantityFormatterService;

#[Layout('layouts.app')]
#[Title('Stock Report')]
class ReportsStock extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouseFilter = '';
    public $kitchenFilter = '';
    public $locationFilter = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    public $groupBy = 'item'; // item, category, location, warehouse
    public $showValue = true;
    public $showBatches = false;
    public $perPage = 25;
    public $sortField = 'name';
    public $sortDirection = 'asc';

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getReportDataProperty()
    {
        $formatter = new QuantityFormatterService();

        $items = Item::with([
            'category', 'baseUnit', 'purchaseUnit', 'issueUnit',
            'defaultStorageLocation.warehouse',
            'activeBatches.storageLocation.warehouse'
        ])->active();

        if ($this->search) {
            $items->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%")
                ->orWhere('barcode', 'like', "%{$this->search}%");
        }

        if ($this->categoryFilter) {
            $items->where('category_id', $this->categoryFilter);
        }

        if ($this->warehouseFilter) {
            $items->whereHas('defaultStorageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter))
                ->orWhereHas('activeBatches.storageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->kitchenFilter) {
            $items->whereHas('activeBatches.storageLocation.warehouse', fn($q) => $q->where('id', $this->kitchenFilter));
        }

        if ($this->locationFilter) {
            $items->whereHas('activeBatches.storageLocation', fn($q) => $q->where('id', $this->locationFilter));
        }

        $items = $items->orderBy($this->sortField, $this->sortDirection)->get();

        if ($this->statusFilter) {
            $items = $items->filter(fn($item) => $item->stock_status['status'] === $this->statusFilter);
        }

        return $items;
    }

    public function getGroupedDataProperty()
    {
        $data = $this->reportData;
        $grouped = [];

        switch ($this->groupBy) {
            case 'category':
                foreach ($data as $item) {
                    $catName = $item->category->name;
                    if (!isset($grouped[$catName])) {
                        $grouped[$catName] = [
                            'name' => $catName,
                            'items' => [],
                            'total_qty' => 0,
                            'total_value' => 0,
                        ];
                    }
                    $grouped[$catName]['items'][] = $item;
                    $grouped[$catName]['total_qty'] += $item->current_stock;
                    $grouped[$catName]['total_value'] += $item->current_stock * $item->cost_price;
                }
                break;

            case 'location':
                foreach ($data as $item) {
                    foreach ($item->activeBatches as $batch) {
                        $locName = $batch->storageLocation?->name ?? 'Unknown';
                        $whName = $batch->storageLocation?->warehouse?->name ?? '';
                        $key = $whName ? "{$whName} / {$locName}" : $locName;

                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'name' => $key,
                                'items' => [],
                                'total_qty' => 0,
                                'total_value' => 0,
                            ];
                        }
                        $grouped[$key]['items'][] = [
                            'item' => $item,
                            'batch' => $batch,
                            'qty' => $batch->quantity_remaining,
                        ];
                        $grouped[$key]['total_qty'] += $batch->quantity_remaining;
                        $grouped[$key]['total_value'] += $batch->quantity_remaining * $item->cost_price;
                    }
                }
                break;

            case 'warehouse':
                foreach ($data as $item) {
                    foreach ($item->activeBatches as $batch) {
                        $whName = $batch->storageLocation?->warehouse?->name ?? 'Unknown';

                        if (!isset($grouped[$whName])) {
                            $grouped[$whName] = [
                                'name' => $whName,
                                'items' => [],
                                'total_qty' => 0,
                                'total_value' => 0,
                            ];
                        }
                        $grouped[$whName]['items'][] = [
                            'item' => $item,
                            'batch' => $batch,
                            'qty' => $batch->quantity_remaining,
                        ];
                        $grouped[$whName]['total_qty'] += $batch->quantity_remaining;
                        $grouped[$whName]['total_value'] += $batch->quantity_remaining * $item->cost_price;
                    }
                }
                break;

            default: // item
                foreach ($data as $item) {
                    $grouped[$item->id] = [
                        'name' => $item->name,
                        'item' => $item,
                        'total_qty' => $item->current_stock,
                        'total_value' => $item->current_stock * $item->cost_price,
                    ];
                }
                break;
        }

        return $grouped;
    }

    public function getTotalsProperty()
    {
        $data = $this->reportData;
        return [
            'total_items' => $data->count(),
            'total_qty' => $data->sum('current_stock'),
            'total_value' => $data->sum(fn($item) => $item->current_stock * $item->cost_price),
            'low_stock' => $data->filter(fn($item) => in_array($item->stock_status['status'], ['out_of_stock', 'below_min', 'needs_reorder']))->count(),
        ];
    }

    public function render()
    {
        return view('livewire.reports.stock', [
            'groupedData' => $this->groupedData,
            'totals' => $this->totals,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'categories' => Category::active()->whereNull('parent_id')->get(),
        ]);
    }
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Stock Report</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Current stock levels with smart quantity breakdown</p>
        </div>
        <div class="flex items-center space-x-2">
            <x-button wire:click="$dispatch('print-report')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
                <i class="fas fa-print"></i>
                <span>Print</span>
            </x-button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Items</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['total_items'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Quantity</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($totals['total_qty'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Value</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-300">${{ number_format($totals['total_value'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Low Stock Items</p>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-300">{{ $totals['low_stock'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search items..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="categoryFilter" :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->toArray()" placeholder="Category" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="kitchenFilter" :options="['' => 'All Kitchens'] + $kitchens->pluck('name', 'id')->toArray()" placeholder="Kitchen" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="locationFilter" :options="['' => 'All Locations'] + $storageLocations->pluck('name', 'id')->toArray()" placeholder="Location" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'out_of_stock' => 'Out of Stock', 'below_min' => 'Below Min', 'needs_reorder' => 'Needs Reorder', 'optimal' => 'Optimal', 'overstocked' => 'Overstocked']" placeholder="Stock Status" />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <label class="text-sm text-slate-700 dark:text-slate-300">Group By:</label>
            <x-plain-select wire:model.live="groupBy" :options="['item' => 'By Item', 'category' => 'By Category', 'location' => 'By Location', 'warehouse' => 'By Warehouse']" class="w-48" />
            <label class="flex items-center space-x-2 cursor-pointer ms-4">
                <input type="checkbox" wire:model="showValue" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Show Value</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" wire:model="showBatches" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Show Batch Details</span>
            </label>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($groupBy === 'item')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">SKU</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Item <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Base Unit</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Current Stock</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Smart Breakdown</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        @if($showValue)
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Cost Price</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Value</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($groupedData as $group)
                    @php $item = $group['item']; @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ $item->sku }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900 dark:text-white">{{ $item->name }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->category->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->baseUnit->name }} ({{ $item->baseUnit->symbol }})</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($item->current_stock, 2) }} {{ $item->baseUnit->symbol }}</td>
                        <td class="px-4 py-3 text-end text-sm text-slate-500 dark:text-slate-400">
                            {{ app(\App\Services\QuantityFormatterService::class)->formatQuantity($item, $item->current_stock) }}
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
                        @if($showValue)
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">${{ number_format($item->cost_price, 2) }}</td>
                        <td class="px-4 py-3 text-end font-mono font-medium text-slate-900 dark:text-white">${{ number_format($item->current_stock * $item->cost_price, 2) }}</td>
                        @endif
                    </tr>
                    @endforeach
                    @if(empty($groupedData))
                    <tr>
                        <td colspan="{{ $showValue ? 9 : 7 }}" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No items found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @elseif($groupBy === 'category')
        <div class="space-y-4 p-4">
            @foreach($groupedData as $group)
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div class="bg-slate-50/80 dark:bg-slate-800/80 px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="font-medium text-slate-900 dark:text-white">{{ $group['name'] }}</h4>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Items: {{ count($group['items']) }}</span>
                        <span class="text-slate-500 dark:text-slate-400">Total Qty: {{ number_format($group['total_qty'], 2) }}</span>
                        @if($showValue)
                        <span class="font-medium text-emerald-600 dark:text-emerald-300">Value: ${{ number_format($group['total_value'], 2) }}</span>
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">SKU</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Item</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Qty</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Breakdown</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Status</th>
                                @if($showValue)
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Value</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($group['items'] as $item)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2 text-sm font-mono text-slate-900 dark:text-white">{{ $item->sku }}</td>
                                <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $item->name }}</td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">{{ number_format($item->current_stock, 2) }} {{ $item->baseUnit->symbol }}</td>
                                <td class="px-4 py-2 text-end text-xs text-slate-500 dark:text-slate-400">{{ app(\App\Services\QuantityFormatterService::class)->formatQuantity($item, $item->current_stock) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
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
                                @if($showValue)
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">${{ number_format($item->current_stock * $item->cost_price, 2) }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="space-y-4 p-4">
            @foreach($groupedData as $group)
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div class="bg-slate-50/80 dark:bg-slate-800/80 px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="font-medium text-slate-900 dark:text-white">{{ $group['name'] }}</h4>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Batches: {{ count($group['items']) }}</span>
                        <span class="text-slate-500 dark:text-slate-400">Total Qty: {{ number_format($group['total_qty'], 2) }}</span>
                        @if($showValue)
                        <span class="font-medium text-emerald-600 dark:text-emerald-300">Value: ${{ number_format($group['total_value'], 2) }}</span>
                        @endif
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Item</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Batch #</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Qty</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Expiry</th>
                                @if($showValue)
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Value</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($group['items'] as $entry)
                            @php $item = $entry['item']; $batch = $entry['batch']; $qty = $entry['qty']; @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $item->name }}</td>
                                <td class="px-4 py-2 text-sm font-mono text-slate-900 dark:text-white">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">{{ number_format($qty, 2) }} {{ $item->baseUnit->symbol }}</td>
                                <td class="px-4 py-2 text-center text-sm">{{ $batch->expiry_date?->format('M d, Y') ?? 'No expiry' }}</td>
                                @if($showValue)
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">${{ number_format($qty * $item->cost_price, 2) }}</td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('print-report', () => {
            window.print();
        });
    });
</script>






