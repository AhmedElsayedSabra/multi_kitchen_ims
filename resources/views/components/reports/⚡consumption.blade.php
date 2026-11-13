<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\StockVoucher;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Kitchen;
use App\Models\StorageLocation;
use App\Models\Category;

new #[Layout('layouts.app')] #[Title('Consumption Report')] class extends Component
{
    use WithPagination;

    public $dateFrom = '';
    public $dateTo = '';
    public $warehouseFilter = '';
    public $kitchenFilter = '';
    public $categoryFilter = '';
    public $itemFilter = '';
    public $groupBy = 'item'; // item, category, kitchen, date
    public $perPage = 25;
    public $sortField = 'total_qty';
    public $sortDirection = 'desc';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

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
        $query = StockVoucher::with([
            'items.item.category', 'items.item.baseUnit',
            'warehouse', 'kitchen', 'fromStorageLocation', 'toStorageLocation'
        ])
            ->where('voucher_type', 'issue')
            ->where('status', 'posted')
            ->whereBetween('voucher_date', [$this->dateFrom, $this->dateTo]);

        if ($this->warehouseFilter) {
            $query->where('warehouse_id', $this->warehouseFilter);
        }

        if ($this->kitchenFilter) {
            $query->where('kitchen_id', $this->kitchenFilter);
        }

        if ($this->categoryFilter) {
            $query->whereHas('items.item', fn($q) => $q->where('category_id', $this->categoryFilter));
        }

        if ($this->itemFilter) {
            $query->whereHas('items', fn($q) => $q->where('item_id', $this->itemFilter));
        }

        return $query->orderBy('voucher_date', 'desc')->get();
    }

    public function getConsumptionDataProperty()
    {
        $vouchers = $this->reportData;
        $consumption = [];

        foreach ($vouchers as $voucher) {
            foreach ($voucher->items as $voucherItem) {
                $item = $voucherItem->item;
                $key = match($this->groupBy) {
                    'category' => $item->category->name,
                    'kitchen' => $voucher->kitchen?->name ?? 'Unknown Kitchen',
                    'date' => $voucher->voucher_date->format('Y-m-d'),
                    default => $item->id,
                };

                if (!isset($consumption[$key])) {
                    $consumption[$key] = [
                        'name' => match($this->groupBy) {
                            'category' => $item->category->name,
                            'kitchen' => $voucher->kitchen?->name ?? 'Unknown Kitchen',
                            'date' => $voucher->voucher_date->format('M d, Y'),
                            default => $item->name,
                        },
                        'item' => $this->groupBy === 'item' ? $item : null,
                        'category' => $this->groupBy === 'category' ? $item->category : null,
                        'kitchen' => $this->groupBy === 'kitchen' ? $voucher->kitchen : null,
                        'date' => $this->groupBy === 'date' ? $voucher->voucher_date : null,
                        'total_qty' => 0,
                        'total_value' => 0,
                        'voucher_count' => 0,
                        'unit' => $voucherItem->unit?->symbol ?? $item->baseUnit->symbol,
                    ];
                }

                $consumption[$key]['total_qty'] += $voucherItem->quantity;
                $consumption[$key]['total_value'] += $voucherItem->quantity * ($voucherItem->cost_price ?? $item->cost_price);
                $consumption[$key]['voucher_count']++;
            }
        }

        // Sort
        uasort($consumption, function($a, $b) {
            $dir = $this->sortDirection === 'asc' ? 1 : -1;
            return $dir * ($a[$this->sortField] <=> $b[$this->sortField]);
        });

        return collect($consumption);
    }

    public function getTotalsProperty()
    {
        $data = $this->consumptionData;
        return [
            'total_qty' => $data->sum('total_qty'),
            'total_value' => $data->sum('total_value'),
            'total_vouchers' => $this->reportData->count(),
            'unique_items' => $data->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'consumptionData' => $this->consumptionData,
            'totals' => $this->totals,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
            'categories' => Category::active()->whereNull('parent_id')->get(),
            'items' => Item::active()->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Consumption Report</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Track stock issued to kitchens and consumption patterns</p>
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
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Issued</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($totals['total_qty'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Value</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-300">${{ number_format($totals['total_value'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Issue Vouchers</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['total_vouchers'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Unique Items</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['unique_items'] }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
            <div class="w-full sm:w-48">
                <x-input label="From Date" type="date" wire:model="dateFrom" class="w-full" />
            </div>
            <div class="w-full sm:w-48">
                <x-input label="To Date" type="date" wire:model="dateTo" class="w-full" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="kitchenFilter" :options="['' => 'All Kitchens'] + $kitchens->pluck('name', 'id')->toArray()" placeholder="Kitchen" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="categoryFilter" :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->toArray()" placeholder="Category" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="itemFilter" :options="['' => 'All Items'] + $items->pluck('name', 'id')->toArray()" placeholder="Item" searchable />
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-4 border-t border-slate-200 dark:border-slate-700">
            <label class="text-sm text-slate-700 dark:text-slate-300">Group By:</label>
            <x-plain-select wire:model.live="groupBy" :options="['item' => 'By Item', 'category' => 'By Category', 'kitchen' => 'By Kitchen', 'date' => 'By Date']" class="w-48" />
            <label class="text-sm text-slate-700 dark:text-slate-300">Sort By:</label>
            <x-plain-select wire:model.live="sortField" :options="['name' => 'Name', 'total_qty' => 'Quantity', 'total_value' => 'Value', 'voucher_count' => 'Voucher Count']" class="w-40" />
            <x-plain-select wire:model.live="sortDirection" :options="['asc' => 'Ascending', 'desc' => 'Descending']" class="w-32" />
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        @if($groupBy === 'date')
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Date <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        @elseif($groupBy === 'kitchen')
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Kitchen <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        @elseif($groupBy === 'category')
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Category <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        @else
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('name')">
                            Item <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        @endif
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('total_qty')">
                            Total Qty <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('total_value')">
                            Total Value <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('voucher_count')">
                            Vouchers <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Unit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($consumptionData as $data)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $data['name'] }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($data['total_qty'], 2) }}</td>
                        <td class="px-4 py-3 text-end font-mono font-medium text-emerald-600 dark:text-emerald-300">${{ number_format($data['total_value'], 2) }}</td>
                        <td class="px-4 py-3 text-center font-mono text-slate-900 dark:text-white">{{ $data['voucher_count'] }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $data['unit'] }}</td>
                    </tr>
                    @endforeach
                    @if($consumptionData->isEmpty())
                    <tr>
                        <td colspan="5" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No consumption data found for the selected period</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Totals Row -->
        <div class="px-4 py-3 bg-slate-50/80 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 font-medium">
            <div class="flex justify-between">
                <span>Totals</span>
                <div class="flex items-center space-x-8">
                    <span class="font-mono">{{ number_format($totals['total_qty'], 2) }}</span>
                    <span class="font-mono text-emerald-600 dark:text-emerald-300">${{ number_format($totals['total_value'], 2) }}</span>
                    <span class="font-mono">{{ $totals['total_vouchers'] }}</span>
                    <span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Trend Chart (simple table) -->
    @if($groupBy !== 'date' && $consumptionData->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Daily Consumption Trend</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <th class="pb-2">Date</th>
                        <th class="pb-2 text-end">Qty Issued</th>
                        <th class="pb-2 text-end">Value</th>
                        <th class="pb-2 text-end">Vouchers</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $dailyData = $this->reportData->groupBy(fn($v) => $v->voucher_date->format('Y-m-d'))
                            ->map(fn($vouchers) => [
                                'qty' => $vouchers->flatMap->items->sum('quantity'),
                                'value' => $vouchers->flatMap->items->sum(fn($i) => $i->quantity * ($i->cost_price ?? $i->item->cost_price)),
                                'count' => $vouchers->count(),
                            ])
                            ->sortKeys()
                            ->take(30); // Last 30 days
                    @endphp
                    @foreach($dailyData as $date => $data)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2">{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                        <td class="py-2 text-end font-mono">{{ number_format($data['qty'], 2) }}</td>
                        <td class="py-2 text-end font-mono text-emerald-600 dark:text-emerald-300">${{ number_format($data['value'], 2) }}</td>
                        <td class="py-2 text-end font-mono">{{ $data['count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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







