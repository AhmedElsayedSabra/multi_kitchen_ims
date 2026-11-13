<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use App\Models\ItemBatch;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\StorageLocation;

new #[Layout('layouts.app')] #[Title('Batch Tracking')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $warehouseFilter = '';
    public $locationFilter = '';
    public $expiryFilter = '';
    public $itemFilter = '';
    public $perPage = 15;
    public $sortField = 'expiry_date';
    public $sortDirection = 'asc';
    public $showOnlyExpiring = false;
    public $selectedBatch = null;
    public $showFEFOModal = false;

    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openFEFOModal(Item $item): void
    {
        $this->selectedBatch = $item;
        $this->showFEFOModal = true;
    }

    public function getBatchesProperty()
    {
        $query = ItemBatch::with(['item.baseUnit', 'storageLocation.warehouse', 'item.category'])
            ->where('quantity_remaining', '>', 0);

        if ($this->search) {
            $query->whereHas('item', fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sku', 'like', "%{$this->search}%"))
                ->orWhere('batch_number', 'like', "%{$this->search}%");
        }

        if ($this->warehouseFilter) {
            $query->whereHas('storageLocation', fn($q) => $q->where('warehouse_id', $this->warehouseFilter));
        }

        if ($this->locationFilter) {
            $query->where('storage_location_id', $this->locationFilter);
        }

        if ($this->itemFilter) {
            $query->where('item_id', $this->itemFilter);
        }

        if ($this->expiryFilter) {
            $now = now();
            switch ($this->expiryFilter) {
                case 'expired':
                    $query->where('expiry_date', '<', $now);
                    break;
                case 'today':
                    $query->whereDate('expiry_date', $now);
                    break;
                case 'week':
                    $query->whereBetween('expiry_date', [$now, $now->copy()->addDays(7)]);
                    break;
                case 'month':
                    $query->whereBetween('expiry_date', [$now, $now->copy()->addDays(30)]);
                    break;
            }
        }

        if ($this->showOnlyExpiring) {
            $query->where('expiry_date', '<=', now()->addDays(30));
        }

        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getExpiringSoonCountProperty()
    {
        return ItemBatch::where('quantity_remaining', '>', 0)
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>=', now())
            ->count();
    }

    public function getExpiredCountProperty()
    {
        return ItemBatch::where('quantity_remaining', '>', 0)
            ->where('expiry_date', '<', now())
            ->count();
    }

    public function getTotalBatchesProperty()
    {
        return ItemBatch::where('quantity_remaining', '>', 0)->count();
    }

    public function getTotalValueProperty()
    {
        return ItemBatch::with('item')->where('quantity_remaining', '>', 0)
            ->get()->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0));
    }

    public function render()
    {
        return $this->view([
            'batches' => $this->batches,
            'warehouses' => Warehouse::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
            'items' => Item::active()->get(),
            'totalBatches' => $this->totalBatches,
            'expiringSoonCount' => $this->expiringSoonCount,
            'expiredCount' => $this->expiredCount,
            'totalValue' => $this->totalValue,
        ]);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Batch Tracking & Expiry</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Monitor batch expiry dates, FEFO selection, and location tracking</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Active Batches</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $totalBatches }}</p>
                </div>
                <div class="p-3 bg-sky-50 dark:bg-sky-900/30 rounded-xl">
                    <i class="fas fa-boxes text-2xl text-sky-600 dark:text-sky-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Expiring Soon (30 days)</p>
                    <p class="text-3xl font-bold text-amber-600 dark:text-amber-300">{{ $expiringSoonCount }}</p>
                </div>
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl">
                    <i class="fas fa-clock text-2xl text-amber-600 dark:text-amber-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Already Expired</p>
                    <p class="text-3xl font-bold text-rose-600 dark:text-rose-300">{{ $expiredCount }}</p>
                </div>
                <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-xl">
                    <i class="fas fa-skull-crossbones text-2xl text-rose-600 dark:text-rose-300"></i>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Total Batch Value</p>
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
                <x-input wire:model.debounce.300ms="search" placeholder="Search batches (item name, SKU, batch #)..." />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Filter by Warehouse" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="locationFilter" :options="['' => 'All Locations'] + $storageLocations->pluck('name', 'id')->toArray()" placeholder="Filter by Location" searchable />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="expiryFilter" :options="['' => 'All Expiry', 'expired' => 'Expired', 'today' => 'Expires Today', 'week' => 'This Week', 'month' => 'This Month']" placeholder="Expiry Status" />
            </div>
            <div class="w-full sm:w-48">
                <x-plain-select wire:model.live="itemFilter" :options="['' => 'All Items'] + $items->pluck('name', 'id')->toArray()" placeholder="Filter by Item" searchable />
            </div>
            <label class="flex items-center space-x-2 cursor-pointer">
                <input type="checkbox" wire:model="showOnlyExpiring" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-slate-700 dark:text-slate-300">Expiring Only</span>
            </label>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Batch #</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('item.name')">
                            Item <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty Remaining</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('expiry_date')">
                            Expiry Date <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Days Left</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Value</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">FEFO</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($batches as $batch)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30
                        @if($batch->days_until_expiry !== null && $batch->days_until_expiry <= 0) bg-rose-50/50 dark:bg-rose-900/10
                        @elseif($batch->days_until_expiry !== null && $batch->days_until_expiry <= 7) bg-amber-50/50 dark:bg-amber-900/10
                        @elseif($batch->days_until_expiry !== null && $batch->days_until_expiry <= 30) bg-amber-50/50 dark:bg-amber-900/10
                        @endif
                    ">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $batch->batch_number }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center space-x-2">
                                @if($batch->item->image)
                                <img src="{{ Storage::url($batch->item->image) }}" alt="" class="w-8 h-8 rounded object-cover">
                                @else
                                <div class="w-8 h-8 rounded bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                    <i class="fas fa-box text-slate-400 dark:text-slate-500"></i>
                                </div>
                                @endif
                                <div>
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $batch->item->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $batch->item->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($batch->storageLocation)
                            {{ $batch->storageLocation->warehouse->name }} / {{ $batch->storageLocation->name }}
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">
                            {{ number_format($batch->quantity_remaining, 2) }} {{ $batch->item->baseUnit->symbol }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($batch->expiry_date)
                            <span class="{{ $batch->days_until_expiry !== null && $batch->days_until_expiry <= 0 ? 'text-rose-600 font-medium' : '' }}">{{ $batch->expiry_date->format('M d, Y') }}</span>
                            @else
                            <span class="text-slate-400 dark:text-slate-500">No expiry</span>
                            @endif
                        </td>
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
                                {{ $batch->days_until_expiry }} days
                            </span>
                            @else
                            <span class="text-slate-400 dark:text-slate-500">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-end font-mono text-slate-900 dark:text-white">
                            ${{ number_format($batch->quantity_remaining * ($batch->item->cost_price ?? 0), 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:loading.attr="disabled" wire:click="openFEFOModal({{ $batch->item->id }})" class="px-3 py-1 text-xs bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900/50" title="FEFO Selection">
                                <i class="fas fa-sort-amount-up"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                    @if($batches->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-10"><div class="mx-auto flex max-w-sm flex-col items-center text-center"><span class="grid size-11 place-items-center rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm.5.3 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-3 text-sm font-medium text-slate-700 dark:text-slate-200">No active batches found</p></div></td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $batches->links() }}
        </div>
    </div>

    {{-- FEFO Modal --}}
    @if($showFEFOModal && $selectedBatch)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">FEFO Selection for {{ $selectedBatch->name }}</h3>
                    <button wire:loading.attr="disabled" wire:click="showFEFOModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-slate-600 dark:text-slate-400">
                        First Expired First Out (FEFO) - Batches sorted by earliest expiry date first.
                        Use this order for stock issues to minimize waste.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                    <th class="pb-2">Priority</th>
                                    <th class="pb-2">Batch #</th>
                                    <th class="pb-2">Location</th>
                                    <th class="pb-2">Qty Available</th>
                                    <th class="pb-2">Expiry Date</th>
                                    <th class="pb-2">Days Left</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $fefoBatches = $selectedBatch->activeBatches()
                                        ->where('quantity_remaining', '>', 0)
                                        ->orderBy('expiry_date', 'asc')
                                        ->get();
                                @endphp
                                @foreach($fefoBatches as $index => $batch)
                                <tr class="border-b border-slate-100 dark:border-slate-800 {{ $batch->days_until_expiry !== null && $batch->days_until_expiry <= 0 ? 'bg-rose-50/50 dark:bg-rose-900/10' : '' }}">
                                    <td class="py-2 font-bold text-indigo-600 dark:text-indigo-400">{{ $index + 1 }}</td>
                                    <td class="py-2 font-mono">{{ $batch->batch_number }}</td>
                                    <td class="py-2">{{ $batch->storageLocation->name ?? '—' }}</td>
                                    <td class="py-2 font-mono">{{ number_format($batch->quantity_remaining, 2) }} {{ $selectedBatch->baseUnit->symbol }}</td>
                                    <td class="py-2">{{ $batch->expiry_date?->format('M d, Y') ?? 'No expiry' }}</td>
                                    <td class="py-2">
                                        @if($batch->days_until_expiry !== null)
                                        <span class="{{ $batch->days_until_expiry <= 0 ? 'text-rose-600' : ($batch->days_until_expiry <= 7 ? 'text-amber-600' : 'text-emerald-600') }}">
                                            {{ $batch->days_until_expiry }} days
                                        </span>
                                        @else
                                        —
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                @if($fefoBatches->isEmpty())
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">No active batches for this item</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-slate-50/80 dark:bg-slate-800/80 rounded-lg p-4">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Total Available</h4>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($fefoBatches->sum('quantity_remaining'), 2) }} {{ $selectedBatch->baseUnit->symbol }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Location Report Section --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Stock by Location</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                        <th class="pb-2">Warehouse</th>
                        <th class="pb-2">Location</th>
                        <th class="pb-2">Type</th>
                        <th class="pb-2">Batches</th>
                        <th class="pb-2">Total Qty</th>
                        <th class="pb-2">Value</th>
                        <th class="pb-2">Expiring Soon</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $locationReport = StorageLocation::with(['warehouse', 'itemBatches.item'])
                            ->where('status', 'active')
                            ->get()
                            ->map(function ($loc) {
                                $batches = $loc->itemBatches()->where('quantity_remaining', '>', 0)->get();
                                return [
                                    'warehouse' => $loc->warehouse->name,
                                    'location' => $loc->name,
                                    'type' => $loc->type,
                                    'batch_count' => $batches->count(),
                                    'total_qty' => $batches->sum('quantity_remaining'),
                                    'value' => $batches->sum(fn($b) => $b->quantity_remaining * ($b->item->cost_price ?? 0)),
                                    'expiring' => $batches->where('expiry_date', '<=', now()->addDays(30))->where('expiry_date', '>=', now())->count(),
                                ];
                            })
                            ->filter(fn($l) => $l['batch_count'] > 0)
                            ->values();
                    @endphp
                    @foreach($locationReport as $loc)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2">{{ $loc['warehouse'] }}</td>
                        <td class="py-2 font-medium">{{ $loc['location'] }}</td>
                        <td class="py-2">
                            @if($loc['type'])
                            <x-badge :color="['cold' => 'blue', 'freezer' => 'indigo', 'dry' => 'amber', 'shelf' => 'gray'][$loc['type']] ?? 'gray'">{{ ucfirst($loc['type']) }}</x-badge>
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-2">{{ $loc['batch_count'] }}</td>
                        <td class="py-2 font-mono">{{ number_format($loc['total_qty'], 2) }}</td>
                        <td class="py-2">${{ number_format($loc['value'], 2) }}</td>
                        <td class="py-2">{{ $loc['expiring'] > 0 ? '<span class="text-amber-600 font-medium">' . $loc['expiring'] . '</span>' : '—' }}</td>
                    </tr>
                    @endforeach
                    @if($locationReport->isEmpty())
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400">No stock in any location</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>






