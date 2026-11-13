<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\ItemBatch;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\StorageLocation;
use App\Models\Category;

new #[Layout('layouts.app')] #[Title('Expiry Report')] class extends Component
{
    use WithPagination;

    public $warehouseFilter = '';
    public $locationFilter = '';
    public $categoryFilter = '';
    public $itemFilter = '';
    public $daysAhead = 30;
    public $showExpired = true;
    public $groupBy = 'item'; // item, batch, location, category
    public $perPage = 25;
    public $sortField = 'expiry_date';
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

    public function getExpiryDataProperty()
    {
        $query = ItemBatch::with([
            'item.category', 'item.baseUnit',
            'storageLocation.warehouse'
        ])
            ->where('quantity_remaining', '>', 0)
            ->whereNotNull('expiry_date');

        if ($this->showExpired) {
            $query->where('expiry_date', '<=', now()->addDays($this->daysAhead));
        } else {
            $query->whereBetween('expiry_date', [now(), now()->addDays($this->daysAhead)]);
        }

        if ($this->warehouseFilter) {
            $query->whereHas('storageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->locationFilter) {
            $query->where('storage_location_id', $this->locationFilter);
        }

        if ($this->categoryFilter) {
            $query->whereHas('item', fn($q) => $q->where('category_id', $this->categoryFilter));
        }

        if ($this->itemFilter) {
            $query->where('item_id', $this->itemFilter);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getGroupedDataProperty()
    {
        $batches = $this->expiryData->getCollection();
        $grouped = [];

        foreach ($batches as $batch) {
            $key = match($this->groupBy) {
                'batch' => $batch->id,
                'location' => $batch->storageLocation?->id ?? 0,
                'category' => $batch->item->category_id,
                default => $batch->item_id,
            };

            $value = match($this->groupBy) {
                'batch' => $batch->quantity_remaining * ($batch->item->cost_price ?? 0),
                default => $batch->quantity_remaining * ($batch->item->cost_price ?? 0),
            };

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'name' => match($this->groupBy) {
                        'batch' => $batch->batch_number,
                        'location' => $batch->storageLocation?->name ?? 'Unknown',
                        'category' => $batch->item->category->name,
                        default => $batch->item->name,
                    },
                    'item' => $this->groupBy === 'item' ? $batch->item : null,
                    'batch' => $this->groupBy === 'batch' ? $batch : null,
                    'location' => $this->groupBy === 'location' ? $batch->storageLocation : null,
                    'category' => $this->groupBy === 'category' ? $batch->item->category : null,
                    'total_qty' => 0,
                    'total_value' => 0,
                    'batches' => [],
                    'unit' => $batch->item->baseUnit->symbol,
                ];
            }

            $grouped[$key]['total_qty'] += $batch->quantity_remaining;
            $grouped[$key]['total_value'] += $value;
            $grouped[$key]['batches'][] = $batch;
        }

        // Sort
        uasort($grouped, function($a, $b) {
            $dir = $this->sortDirection === 'asc' ? 1 : -1;
            return $dir * ($a[$this->sortField] <=> $b[$this->sortField]);
        });

        return collect($grouped);
    }

    public function getTotalsProperty()
    {
        $batches = $this->expiryData->getCollection();
        $expired = $batches->filter(fn($b) => $b->days_until_expiry !== null && $b->days_until_expiry <= 0);
        $expiringToday = $batches->filter(fn($b) => $b->days_until_expiry === 0);
        $expiringWeek = $batches->filter(fn($b) => $b->days_until_expiry !== null && $b->days_until_expiry > 0 && $b->days_until_expiry <= 7);
        $expiringMonth = $batches->filter(fn($b) => $b->days_until_expiry !== null && $b->days_until_expiry > 7 && $b->days_until_expiry <= 30);

        return [
            'total_batches' => $batches->count(),
            'total_qty' => $batches->sum('quantity_remaining'),
            'total_value' => $batches->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0)),
            'expired_qty' => $expired->sum('quantity_remaining'),
            'expired_value' => $expired->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0)),
            'today_qty' => $expiringToday->sum('quantity_remaining'),
            'week_qty' => $expiringWeek->sum('quantity_remaining'),
            'month_qty' => $expiringMonth->sum('quantity_remaining'),
        ];
    }

    public function render()
    {
        return $this->view([
            'expiryData' => $this->expiryData,
            'groupedData' => $this->groupedData,
            'totals' => $this->totals,
            'warehouses' => Warehouse::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'categories' => Category::active()->whereNull('parent_id')->get(),
            'items' => Item::active()->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Expiry Report</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Track expiring and expired batches for waste reduction</p>
        </div>
        <div class="flex items-center space-x-2">
            <x-button wire:click="$dispatch('print-report')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
                <i class="fas fa-print"></i>
                <span>Print</span>
            </x-button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Batches</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['total_batches'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-rose-200 dark:border-rose-800 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Expired</p>
            <p class="text-3xl font-bold text-rose-600 dark:text-rose-300">{{ number_format($totals['expired_qty'], 2) }}</p>
            <p class="text-sm text-rose-600 dark:text-rose-300 mt-1">${{ number_format($totals['expired_value'], 2) }} value</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-rose-200 dark:border-rose-800 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Expires Today</p>
            <p class="text-3xl font-bold text-rose-600 dark:text-rose-300">{{ number_format($totals['today_qty'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">This Week</p>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-300">{{ number_format($totals['week_qty'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-amber-200 dark:border-amber-800 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">This Month</p>
            <p class="text-3xl font-bold text-amber-600 dark:text-amber-300">{{ number_format($totals['month_qty'], 2) }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
            <div class="w-full sm:w-48">
                <x-input label="Days Ahead" type="number" wire:model="daysAhead" min="1" max="365" class="w-full" />
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
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="itemFilter" :options="['' => 'All Items'] + $items->pluck('name', 'id')->toArray()" placeholder="Item" searchable />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" wire:model="showExpired" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Include Expired</span>
            </label>
            <label class="text-sm text-slate-700 dark:text-slate-300">Group By:</label>
            <x-plain-select wire:model.live="groupBy" :options="['item' => 'By Item', 'batch' => 'By Batch', 'location' => 'By Location', 'category' => 'By Category']" class="w-48" />
            <label class="text-sm text-slate-700 dark:text-slate-300">Sort By:</label>
            <x-plain-select wire:model.live="sortField" :options="['expiry_date' => 'Expiry Date', 'name' => 'Name', 'total_qty' => 'Quantity', 'total_value' => 'Value']" class="w-40" />
            <x-plain-select wire:model.live="sortDirection" :options="['asc' => 'Ascending', 'desc' => 'Descending']" class="w-32" />
        </div>
    </div>

    <!-- Expiry Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if($groupBy === 'batch')
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Batch #</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('expiry_date')">
                            Expiry Date <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Days Left</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($expiryData as $batch)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30
                        @if($batch->days_until_expiry !== null && $batch->days_until_expiry <= 0) bg-rose-50/50 dark:bg-rose-900/10
                        @elseif($batch->days_until_expiry !== null && $batch->days_until_expiry <= 7) bg-amber-50/50 dark:bg-amber-900/10
                        @elseif($batch->days_until_expiry !== null && $batch->days_until_expiry <= 30) bg-amber-50/50 dark:bg-amber-900/10
                        @endif
                    ">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $batch->batch_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $batch->item->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $batch->item->category->name }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($batch->storageLocation)
                            {{ $batch->storageLocation->warehouse->name }} / {{ $batch->storageLocation->name }}
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($batch->quantity_remaining, 2) }} {{ $batch->item->baseUnit->symbol }}</td>
                        <td class="px-4 py-3 text-center {{ $batch->days_until_expiry !== null && $batch->days_until_expiry <= 0 ? 'text-rose-600 font-medium' : '' }}">{{ $batch->expiry_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($batch->days_until_expiry !== null)
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @if($batch->days_until_expiry <= 0) bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                @elseif($batch->days_until_expiry <= 3) bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                @elseif($batch->days_until_expiry <= 7) bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                @elseif($batch->days_until_expiry <= 30) bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                @else bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                @endif
                            ">
                                {{ $batch->days_until_expiry <= 0 ? 'EXPIRED' : $batch->days_until_expiry . ' days' }}
                            </span>
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">${{ number_format($batch->quantity_remaining * ($batch->item->cost_price ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                    @if($expiryData->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No expiring batches found</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $expiryData->links() }}
        </div>
        @else
        <div class="space-y-4 p-4">
            @foreach($groupedData as $data)
            <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <div class="bg-slate-50/80 dark:bg-slate-800/80 px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h4 class="font-medium text-slate-900 dark:text-white">{{ $data['name'] }}</h4>
                    <div class="flex items-center space-x-4 text-sm">
                        <span class="text-slate-500 dark:text-slate-400">Batches: {{ count($data['batches']) }}</span>
                        <span class="text-slate-500 dark:text-slate-400">Qty: {{ number_format($data['total_qty'], 2) }} {{ $data['unit'] }}</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-300">Value: ${{ number_format($data['total_value'], 2) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                            <tr>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Batch #</th>
                                <th class="px-4 py-2 text-start text-xs font-medium text-slate-500 dark:text-slate-400">Item</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Qty</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Expiry</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 dark:text-slate-400">Days Left</th>
                                <th class="px-4 py-2 text-end text-xs font-medium text-slate-500 dark:text-slate-400">Value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($data['batches'] as $batch)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30
                                @if($batch->days_until_expiry !== null && $batch->days_until_expiry <= 0) bg-rose-50/50 dark:bg-rose-900/10
                                @elseif($batch->days_until_expiry !== null && $batch->days_until_expiry <= 7) bg-amber-50/50 dark:bg-amber-900/10
                                @endif
                            ">
                                <td class="px-4 py-2 text-sm font-mono text-slate-900 dark:text-white">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-2 text-sm text-slate-900 dark:text-white">{{ $batch->item->name }}</td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">{{ number_format($batch->quantity_remaining, 2) }} {{ $batch->item->baseUnit->symbol }}</td>
                                <td class="px-4 py-2 text-center text-sm {{ $batch->days_until_expiry !== null && $batch->days_until_expiry <= 0 ? 'text-rose-600 font-medium' : '' }}">{{ $batch->expiry_date->format('M d, Y') }}</td>
                                <td class="px-4 py-2 text-center">
                                    @if($batch->days_until_expiry !== null)
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                        @if($batch->days_until_expiry <= 0) bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                        @elseif($batch->days_until_expiry <= 3) bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                        @elseif($batch->days_until_expiry <= 7) bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                        @elseif($batch->days_until_expiry <= 30) bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                        @else bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300
                                        @endif
                                    ">
                                        {{ $batch->days_until_expiry <= 0 ? 'EXPIRED' : $batch->days_until_expiry . 'd' }}
                                    </span>
                                    @else
                                    —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-end font-mono text-slate-900 dark:text-white">${{ number_format($batch->quantity_remaining * ($batch->item->cost_price ?? 0), 2) }}</td>
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

    <!-- Action Required Summary -->
    @if($totals['expired_qty'] > 0 || $totals['today_qty'] > 0 || $totals['week_qty'] > 0)
    <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-rose-900 dark:text-rose-100 mb-4 flex items-center space-x-2">
            <i class="fas fa-exclamation-triangle text-rose-600"></i>
            <span>Action Required</span>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @if($totals['expired_qty'] > 0)
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border border-rose-200 dark:border-rose-800">
                <p class="text-sm text-slate-500 dark:text-slate-400">Expired Items</p>
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-300">{{ number_format($totals['expired_qty'], 2) }}</p>
                <p class="text-sm text-rose-600 dark:text-rose-300">${{ number_format($totals['expired_value'], 2) }} value at risk</p>
            </div>
            @endif
            @if($totals['today_qty'] > 0)
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border border-rose-200 dark:border-rose-800">
                <p class="text-sm text-slate-500 dark:text-slate-400">Expires Today</p>
                <p class="text-2xl font-bold text-rose-600 dark:text-rose-300">{{ number_format($totals['today_qty'], 2) }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Use immediately or dispose</p>
            </div>
            @endif
            @if($totals['week_qty'] > 0)
            <div class="bg-white dark:bg-slate-800 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
                <p class="text-sm text-slate-500 dark:text-slate-400">Expires This Week</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-300">{{ number_format($totals['week_qty'], 2) }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Prioritize in FEFO selection</p>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('print-report', () => {
            window.print();
        });
    });
</script>






