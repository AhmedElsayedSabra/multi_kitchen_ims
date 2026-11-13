<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\StockVoucher;
use App\Models\StockMovement;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Kitchen;
use App\Models\StorageLocation;
use App\Models\ItemBatch;

new #[Layout('layouts.app')] #[Title('Stock Movements Report')] class extends Component
{
    use WithPagination;

    public $dateFrom = '';
    public $dateTo = '';
    public $typeFilter = '';
    public $warehouseFilter = '';
    public $kitchenFilter = '';
    public $itemFilter = '';
    public $locationFilter = '';
    public $perPage = 25;
    public $sortField = 'voucher_date';
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

    public function getMovementsProperty()
    {
        $query = StockMovement::with([
            'item.category', 'item.baseUnit', 'unit',
            'voucher.warehouse', 'voucher.kitchen',
            'voucher.fromStorageLocation', 'voucher.toStorageLocation',
            'storageLocation.warehouse', 'batch'
        ])
            ->whereBetween('created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);

        if ($this->typeFilter) {
            $query->where('movement_type', $this->typeFilter);
        }

        if ($this->warehouseFilter) {
            $query->whereHas('voucher', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->kitchenFilter) {
            $query->whereHas('voucher', fn($q) => $q->where('kitchen_id', $this->kitchenFilter));
        }

        if ($this->itemFilter) {
            $query->where('item_id', $this->itemFilter);
        }

        if ($this->locationFilter) {
            $query->where('storage_location_id', $this->locationFilter);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getTotalsProperty()
    {
        $query = StockMovement::whereBetween('created_at', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);

        if ($this->typeFilter) {
            $query->where('movement_type', $this->typeFilter);
        }

        if ($this->warehouseFilter) {
            $query->whereHas('voucher', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->kitchenFilter) {
            $query->whereHas('voucher', fn($q) => $q->where('kitchen_id', $this->kitchenFilter));
        }

        if ($this->itemFilter) {
            $query->where('item_id', $this->itemFilter);
        }

        if ($this->locationFilter) {
            $query->where('storage_location_id', $this->locationFilter);
        }

        $movements = $query->get();

        return [
            'total_in' => $movements->where('movement_type', 'in')->sum('quantity_base'),
            'total_out' => $movements->where('movement_type', 'out')->sum('quantity_base'),
            'net_change' => $movements->where('movement_type', 'in')->sum('quantity_base') -
                           $movements->where('movement_type', 'out')->sum('quantity_base'),
            'total_movements' => $movements->count(),
        ];
    }

    public function render()
    {
        return $this->view([
            'movements' => $this->movements,
            'totals' => $this->totals,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'items' => Item::active()->get(),
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Stock Movements Report</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Detailed audit trail of all stock movements</p>
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
            <p class="text-sm text-slate-500 dark:text-slate-400">Total In (Base Qty)</p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-300">{{ number_format($totals['total_in'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Out (Base Qty)</p>
            <p class="text-3xl font-bold text-rose-600 dark:text-rose-300">{{ number_format($totals['total_out'], 2) }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Net Change</p>
            <p class="text-3xl font-bold {{ $totals['net_change'] >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                {{ $totals['net_change'] >= 0 ? '+' : '' }}{{ number_format($totals['net_change'], 2) }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Movements</p>
            <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totals['total_movements'] }}</p>
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
                <x-plain-select wire:model.live="typeFilter" :options="['' => 'All Types', 'in' => 'In (Addition/Return/Transfer In)', 'out' => 'Out (Issue/Transfer Out/Adjustment Down)', 'adjustment' => 'Adjustment']" placeholder="Movement Type" />
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
                <x-plain-select wire:model.live="itemFilter" :options="['' => 'All Items'] + $items->pluck('name', 'id')->toArray()" placeholder="Item" searchable />
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('created_at')">
                            Date/Time <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Voucher</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Base Qty</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">From Location</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">To Location</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Batch</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($movements as $movement)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $movement->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">
                            <a href="#" class="hover:underline">{{ $movement->voucher->reference_no }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <x-badge :color="['in' => 'green', 'out' => 'red', 'adjustment' => 'amber'][$movement->movement_type] ?? 'gray'">
                                {{ ucfirst($movement->movement_type) }}
                            </x-badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $movement->item->name }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($movement->quantity, 2) }} {{ $movement->unit->symbol }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">
                            {{ number_format($movement->quantity_base, 2) }} {{ $movement->item->baseUnit->symbol }}
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($movement->voucher->fromStorageLocation)
                            {{ $movement->voucher->fromStorageLocation->name }}
                            @elseif($movement->voucher->warehouse)
                            {{ $movement->voucher->warehouse->name }} (Warehouse)
                            @elseif($movement->voucher->kitchen)
                            {{ $movement->voucher->kitchen->name }} (Kitchen)
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($movement->voucher->toStorageLocation)
                            {{ $movement->voucher->toStorageLocation->name }}
                            @elseif($movement->voucher->kitchen)
                            {{ $movement->voucher->kitchen->name }} (Kitchen)
                            @elseif($movement->voucher->warehouse)
                            {{ $movement->voucher->warehouse->name }} (Warehouse)
                            @else
                            —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ $movement->batch->batch_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">{{ number_format($movement->running_balance, 2) }} {{ $movement->item->baseUnit->symbol }}</td>
                    </tr>
                    @endforeach
                    @if($movements->isEmpty())
                    <tr>
                        <td colspan="10" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No movements found for the selected period</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $movements->links() }}
        </div>
    </div>

    <!-- Summary by Type -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Summary by Movement Type</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <th class="pb-2">Type</th>
                        <th class="pb-2 text-end">Count</th>
                        <th class="pb-2 text-end">Total Qty (Base)</th>
                        <th class="pb-2 text-end">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $typeSummary = $this->movements->getCollection()->groupBy('movement_type')
                            ->map(fn($m) => [
                                'count' => $m->count(),
                                'qty' => $m->sum('quantity_base'),
                                'value' => $m->sum(fn($mov) => $mov->quantity_base * ($mov->item->cost_price ?? 0)),
                            ]);
                    @endphp
                    @foreach(['in', 'out', 'adjustment'] as $type)
                    @if(isset($typeSummary[$type]))
                    @php $data = $typeSummary[$type]; @endphp
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2">
                            <x-badge :color="['in' => 'green', 'out' => 'red', 'adjustment' => 'amber'][$type] ?? 'gray'" class="me-2">{{ ucfirst($type) }}</x-badge>
                        </td>
                        <td class="py-2 text-end font-mono">{{ $data['count'] }}</td>
                        <td class="py-2 text-end font-mono">{{ number_format($data['qty'], 2) }}</td>
                        <td class="py-2 text-end font-mono text-emerald-600 dark:text-emerald-300">${{ number_format($data['value'], 2) }}</td>
                    </tr>
                    @endif
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







