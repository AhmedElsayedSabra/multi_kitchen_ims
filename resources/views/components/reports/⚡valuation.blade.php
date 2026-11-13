<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Warehouse;
use App\Models\StorageLocation;
use App\Models\Category;
use App\Services\QuantityFormatterService;

new #[Layout('layouts.app')] #[Title('Stock Valuation Report')] class extends Component
{
    use WithPagination;

    public $warehouseFilter = '';
    public $locationFilter = '';
    public $categoryFilter = '';
    public $valuationMethod = 'cost'; // cost, average, fifo, lifo
    public $groupBy = 'item'; // item, category, location, warehouse
    public $perPage = 25;
    public $sortField = 'total_value';
    public $sortDirection = 'desc';

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getValuationDataProperty()
    {
        $items = Item::with([
            'category', 'baseUnit', 'defaultStorageLocation.warehouse',
            'activeBatches.storageLocation.warehouse'
        ])->active();

        if ($this->categoryFilter) {
            $items->where('category_id', $this->categoryFilter);
        }

        if ($this->warehouseFilter) {
            $items->whereHas('defaultStorageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter))
                ->orWhereHas('activeBatches.storageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->locationFilter) {
            $items->whereHas('activeBatches.storageLocation', fn($q) => $q->where('id', $this->locationFilter));
        }

        $items = $items->get();

        $valuation = [];

        foreach ($items as $item) {
            $batches = $item->activeBatches;

            if ($batches->isEmpty()) {
                $key = match($this->groupBy) {
                    'category' => $item->category->name,
                    'location' => $item->defaultStorageLocation?->name ?? 'Unassigned',
                    'warehouse' => $item->defaultStorageLocation?->warehouse?->name ?? 'Unassigned',
                    default => $item->id,
                };

                $value = match($this->valuationMethod) {
                    'cost' => $item->current_stock * $item->cost_price,
                    'average' => $item->current_stock * $item->average_cost,
                    'fifo' => $this->calculateFIFO($batches),
                    'lifo' => $this->calculateLIFO($batches),
                };

                if (!isset($valuation[$key])) {
                    $valuation[$key] = [
                        'name' => match($this->groupBy) {
                            'category' => $item->category->name,
                            'location' => $item->defaultStorageLocation?->name ?? 'Unassigned',
                            'warehouse' => $item->defaultStorageLocation?->warehouse?->name ?? 'Unassigned',
                            default => $item->name,
                        },
                        'item' => $this->groupBy === 'item' ? $item : null,
                        'category' => $this->groupBy === 'category' ? $item->category : null,
                        'location' => $this->groupBy === 'location' ? $item->defaultStorageLocation : null,
                        'warehouse' => $this->groupBy === 'warehouse' ? $item->defaultStorageLocation?->warehouse : null,
                        'total_qty' => 0,
                        'total_value' => 0,
                        'unit' => $item->baseUnit->symbol,
                    ];
                }

                $valuation[$key]['total_qty'] += $item->current_stock;
                $valuation[$key]['total_value'] += $value;
            } else {
                foreach ($batches as $batch) {
                    $batchValue = match($this->valuationMethod) {
                        'cost' => $batch->quantity_remaining * $item->cost_price,
                        'average' => $batch->quantity_remaining * $item->average_cost,
                        'fifo' => $batch->quantity_remaining * $item->cost_price, // FIFO uses cost price
                        'lifo' => $batch->quantity_remaining * $item->cost_price, // LIFO uses cost price
                    };

                    $key = match($this->groupBy) {
                        'category' => $item->category->name,
                        'location' => $batch->storageLocation?->name ?? 'Unassigned',
                        'warehouse' => $batch->storageLocation?->warehouse?->name ?? 'Unassigned',
                        default => $item->id,
                    };

                    if (!isset($valuation[$key])) {
                        $valuation[$key] = [
                            'name' => match($this->groupBy) {
                                'category' => $item->category->name,
                                'location' => $batch->storageLocation?->name ?? 'Unassigned',
                                'warehouse' => $batch->storageLocation?->warehouse?->name ?? 'Unassigned',
                                default => $item->name,
                            },
                            'item' => $this->groupBy === 'item' ? $item : null,
                            'category' => $this->groupBy === 'category' ? $item->category : null,
                            'location' => $this->groupBy === 'location' ? $batch->storageLocation : null,
                            'warehouse' => $this->groupBy === 'warehouse' ? $batch->storageLocation?->warehouse : null,
                            'total_qty' => 0,
                            'total_value' => 0,
                            'unit' => $item->baseUnit->symbol,
                        ];
                    }

                    $valuation[$key]['total_qty'] += $batch->quantity_remaining;
                    $valuation[$key]['total_value'] += $batchValue;
                }
            }
        }

        // Sort
        uasort($valuation, function($a, $b) {
            $dir = $this->sortDirection === 'asc' ? 1 : -1;
            return $dir * ($a[$this->sortField] <=> $b[$this->sortField]);
        });

        return collect($valuation);
    }

    private function calculateFIFO($batches)
    {
        // FIFO: Use cost of earliest purchased batches first
        $sorted = $batches->sortBy('created_at');
        return $sorted->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0));
    }

    private function calculateLIFO($batches)
    {
        // LIFO: Use cost of latest purchased batches first
        $sorted = $batches->sortByDesc('created_at');
        return $sorted->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0));
    }

    public function getTotalsProperty()
    {
        $data = $this->valuationData;
        return [
            'total_qty' => $data->sum('total_qty'),
            'total_value' => $data->sum('total_value'),
            'total_lines' => $data->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'valuationData' => $this->valuationData,
            'totals' => $this->totals,
            'warehouses' => Warehouse::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'categories' => Category::active()->whereNull('parent_id')->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Stock Valuation Report</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Inventory valuation using multiple costing methods</p>
        </div>
        <div class="flex items-center space-x-2">
            <x-button wire:click="$dispatch('print-report')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
                <i class="fas fa-print"></i>
                <span>Print</span>
            </x-button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Quantity</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($totals['total_qty'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Valuation ({{ ucfirst($valuationMethod) }})</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-300">${{ number_format($totals['total_value'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Line Items</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['total_lines'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="valuationMethod" :options="['cost' => 'Standard Cost', 'average' => 'Weighted Average', 'fifo' => 'FIFO', 'lifo' => 'LIFO']" class="w-full" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="locationFilter" :options="['' => 'All Locations'] + $storageLocations->pluck('name', 'id')->toArray()" placeholder="Location" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="categoryFilter" :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->toArray()" placeholder="Category" searchable />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <label class="text-sm text-slate-700 dark:text-slate-300">Group By:</label>
            <x-plain-select wire:model.live="groupBy" :options="['item' => 'By Item', 'category' => 'By Category', 'location' => 'By Location', 'warehouse' => 'By Warehouse']" class="w-48" />
            <label class="text-sm text-slate-700 dark:text-slate-300">Sort By:</label>
            <x-plain-select wire:model.live="sortField" :options="['name' => 'Name', 'total_qty' => 'Quantity', 'total_value' => 'Value']" class="w-40" />
            <x-plain-select wire:model.live="sortDirection" :options="['asc' => 'Ascending', 'desc' => 'Descending']" class="w-32" />
        </div>
    </div>

    <!-- Valuation Table -->
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
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('total_qty')">
                            Qty <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Unit</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Unit Cost</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('total_value')">
                            Total Value <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($valuationData as $data)
                    @php $item = $data['item']; @endphp
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ $item->sku }}</td>
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $item->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->category->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $item->defaultStorageLocation?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($data['total_qty'], 2) }}</td>
                        <td class="px-4 py-3 text-end text-sm text-slate-600 dark:text-slate-300">{{ $data['unit'] }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">${{ number_format($item->cost_price, 2) }}</td>
                        <td class="px-4 py-3 text-end font-mono font-medium text-emerald-600 dark:text-emerald-300">${{ number_format($data['total_value'], 2) }}</td>
                    </tr>
                    @endforeach
                    @if($valuationData->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No stock to value</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @else
        <div class="space-y-4 p-4">
            @foreach($valuationData as $data)
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div class="bg-slate-50/80 dark:bg-slate-800/80 px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="font-medium text-slate-900 dark:text-white">{{ $data['name'] }}</h4>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Qty: {{ number_format($data['total_qty'], 2) }} {{ $data['unit'] }}</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-300">Value: ${{ number_format($data['total_value'], 2) }}</span>
                    </div>
                </div>
                @if($groupBy === 'category' || $groupBy === 'location' || $groupBy === 'warehouse')
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Item</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Qty</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Unit Cost</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @php
                                // Get items for this group
                                $items = Item::with(['category', 'baseUnit', 'activeBatches.storageLocation'])->active()->get()
                                    ->filter(function($item) use ($data) {
                                        if ($this->groupBy === 'category') {
                                            return $item->category_id == $data['category']?->id;
                                        } elseif ($this->groupBy === 'location') {
                                            return $item->activeBatches->contains('storage_location_id', $data['location']?->id);
                                        } elseif ($this->groupBy === 'warehouse') {
                                            return $item->activeBatches->contains('storageLocation.warehouse_id', $data['warehouse']?->id);
                                        }
                                        return false;
                                    });
                            @endphp
                            @foreach($items as $item)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $item->name }}</td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">{{ number_format($item->current_stock, 2) }}</td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">${{ number_format($item->cost_price, 2) }}</td>
                                <td class="px-4 py-2 text-end font-mono text-emerald-600 dark:text-emerald-300">${{ number_format($item->current_stock * $item->cost_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Valuation Method Comparison -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Valuation Method Comparison</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <th class="pb-2">Method</th>
                        <th class="pb-2 text-end">Total Value</th>
                        <th class="pb-2 text-end">Variance vs Standard Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $standardCost = Item::with('activeBatches')->active()->get()
                            ->sum(fn($item) => $item->current_stock * $item->cost_price);

                        $averageCost = Item::with('activeBatches')->active()->get()
                            ->sum(fn($item) => $item->current_stock * $item->average_cost);

                        $fifoCost = Item::with('activeBatches')->active()->get()
                            ->sum(fn($item) => $item->activeBatches->sortBy('created_at')
                                ->sum(fn($b) => $b->quantity_remaining * $item->cost_price));

                        $lifoCost = Item::with('activeBatches')->active()->get()
                            ->sum(fn($item) => $item->activeBatches->sortByDesc('created_at')
                                ->sum(fn($b) => $b->quantity_remaining * $item->cost_price));

                        $methods = [
                            ['name' => 'Standard Cost', 'value' => $standardCost],
                            ['name' => 'Weighted Average', 'value' => $averageCost],
                            ['name' => 'FIFO', 'value' => $fifoCost],
                            ['name' => 'LIFO', 'value' => $lifoCost],
                        ];
                    @endphp
                    @foreach($methods as $method)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2 font-medium {{ $method['name'] === ucfirst($valuationMethod) ? 'text-indigo-600 dark:text-indigo-400' : '' }}">{{ $method['name'] }}</td>
                        <td class="py-2 text-end font-mono text-emerald-600 dark:text-emerald-300">${{ number_format($method['value'], 2) }}</td>
                        <td class="py-2 text-end font-mono {{ ($method['value'] - $standardCost) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ ($method['value'] - $standardCost) >= 0 ? '+' : '' }}${{ number_format($method['value'] - $standardCost, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('print-report', () => {
            window.print();
        });
    });
</script>







