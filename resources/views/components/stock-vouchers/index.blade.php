<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\StockVoucher;
use App\Models\Item;
use App\Models\Warehouse;
use App\Models\Kitchen;
use App\Models\StorageLocation;
use App\Models\ItemBatch;

#[Layout('layouts.app')]
#[Title('Stock Vouchers')]
class StockVouchersIndex extends Component
{
    use WithPagination;

    public $search = '';
    #[Url(as: 'type')]
    public $typeFilter = '';
    public $statusFilter = '';
    public $warehouseFilter = '';
    public $kitchenFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public $showCreateModal = false;
    public $showEditModal = false;
    public $showViewModal = false;
    public $selectedVoucher = null;
    public $voucher_type = 'addition';

    // Form fields
    public $voucher_date = '';
    public $reference_no = '';
    public $warehouse_id = '';
    public $kitchen_id = '';
    public $from_storage_location_id = '';
    public $to_storage_location_id = '';
    public $notes = '';

    // Items array
    public $items = [
        ['item_id' => '', 'quantity' => '', 'unit_id' => '', 'batch_number' => '', 'expiry_date' => '', 'storage_location_id' => '', 'cost_price' => ''],
    ];

    protected $rules = [
        'voucher_type' => 'required|in:addition,issue,return,transfer,adjustment',
        'voucher_date' => 'required|date',
        'reference_no' => 'nullable|string|max:100|unique:stock_vouchers,voucher_number',
        'warehouse_id' => 'nullable|exists:warehouses,id',
        'kitchen_id' => 'nullable|exists:kitchens,id',
        'from_storage_location_id' => 'nullable|exists:storage_locations,id',
        'to_storage_location_id' => 'nullable|exists:storage_locations,id',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.item_id' => 'required|exists:items,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_id' => 'required|exists:units,id',
        'items.*.batch_number' => 'nullable|string|max:50',
        'items.*.expiry_date' => 'nullable|date',
        'items.*.storage_location_id' => 'nullable|exists:storage_locations,id',
        'items.*.cost_price' => 'nullable|numeric|min:0',
    ];

    public function mount(): void
    {
        $allowedTypes = ['addition', 'issue', 'return', 'transfer', 'adjustment'];
        $requestedType = request()->query('type');

        $this->typeFilter = in_array($requestedType, $allowedTypes, true) ? $requestedType : '';

        if (request()->boolean('create')) {
            $this->openCreateModal($this->typeFilter ?: 'addition');
        }
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

    public function openCreateModal(string $type = 'addition'): void
    {
        $this->resetForm();
        $this->voucher_type = $type;
        $this->voucher_date = now()->format('Y-m-d');
        $this->showCreateModal = true;
    }

    public function openEditModal(StockVoucher $voucher): void
    {
        $this->selectedVoucher = $voucher;
        $this->voucher_type = $voucher->voucher_type;
        $this->voucher_date = $voucher->transaction_date?->format('Y-m-d') ?? '';
        $this->reference_no = $voucher->voucher_number;
        $this->warehouse_id = $voucher->warehouse_id;
        $this->kitchen_id = $voucher->kitchen_id;
        $this->from_storage_location_id = $voucher->from_storage_location_id;
        $this->to_storage_location_id = $voucher->to_storage_location_id;
        $this->notes = $voucher->notes;

        $this->items = $voucher->items->map(function ($item) {
            return [
                'item_id' => $item->item_id,
                'quantity' => $item->quantity,
                'unit_id' => $item->unit_id,
                'batch_number' => $item->batch_number,
                'expiry_date' => $item->expiry_date?->format('Y-m-d'),
                'storage_location_id' => $item->storage_location_id,
                'cost_price' => $item->cost_price,
            ];
        })->toArray();

        $this->showEditModal = true;
    }

    public function openViewModal(StockVoucher $voucher): void
    {
        $this->selectedVoucher = $voucher;
        $this->showViewModal = true;
    }

    public function resetForm(): void
    {
        $this->voucher_type = 'addition';
        $this->voucher_date = now()->format('Y-m-d');
        $this->reference_no = '';
        $this->warehouse_id = '';
        $this->kitchen_id = '';
        $this->from_storage_location_id = '';
        $this->to_storage_location_id = '';
        $this->notes = '';
        $this->items = [
            ['item_id' => '', 'quantity' => '', 'unit_id' => '', 'batch_number' => '', 'expiry_date' => '', 'storage_location_id' => '', 'cost_price' => ''],
        ];
        $this->selectedVoucher = null;
        $this->resetValidation();
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function addItemRow(): void
    {
        $this->items[] = ['item_id' => '', 'quantity' => '', 'unit_id' => '', 'batch_number' => '', 'expiry_date' => '', 'storage_location_id' => '', 'cost_price' => ''];
    }

    public function removeItemRow($index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function updatedItems($value, $key): void
    {
        if (str_contains($key, 'item_id')) {
            $index = explode('.', $key)[0];
            $item = Item::find($value);
            if ($item) {
                $this->items[$index]['unit_id'] = $item->base_unit_id;
                $this->items[$index]['cost_price'] = $item->cost_price;
                $this->items[$index]['storage_location_id'] = $item->default_storage_location_id;
            }
        }
    }

    public function generateReferenceNo(): void
    {
        $prefix = match($this->voucher_type) {
            'addition' => 'ADD',
            'issue' => 'ISS',
            'return' => 'RET',
            'transfer' => 'TRF',
            'adjustment' => 'ADJ',
        };
        $this->reference_no = $prefix . '-' . now()->format('Ymd') . '-' . str_pad(StockVoucher::where('voucher_type', $this->voucher_type)->count() + 1, 4, '0', STR_PAD_LEFT);
    }

    public function create(): void
    {
        $this->validate();

        $voucher = StockVoucher::create([
            'voucher_type' => $this->voucher_type,
            'transaction_date' => $this->voucher_date,
            'voucher_number' => $this->reference_no,
            'warehouse_id' => $this->warehouse_id ?: null,
            'kitchen_id' => $this->kitchen_id ?: null,
            'from_storage_location_id' => $this->from_storage_location_id ?: null,
            'to_storage_location_id' => $this->to_storage_location_id ?: null,
            'notes' => $this->notes,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        foreach ($this->items as $item) {
            $voucher->items()->create([
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'batch_number' => $item['batch_number'] ?: null,
                'expiry_date' => $item['expiry_date'] ?: null,
                'storage_location_id' => $item['storage_location_id'] ?: null,
                'cost_price' => $item['cost_price'] ?: null,
            ]);
        }

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Voucher Created',
            'message' => "Stock voucher {$voucher->voucher_number} has been created.",
        ]);
    }

    public function update(): void
    {
        $this->validate([
            'voucher_type' => 'required|in:addition,issue,return,transfer,adjustment',
            'voucher_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100|unique:stock_vouchers,voucher_number,' . $this->selectedVoucher->id,
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'kitchen_id' => 'nullable|exists:kitchens,id',
            'from_storage_location_id' => 'nullable|exists:storage_locations,id',
            'to_storage_location_id' => 'nullable|exists:storage_locations,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.storage_location_id' => 'nullable|exists:storage_locations,id',
            'items.*.cost_price' => 'nullable|numeric|min:0',
        ]);

        $this->selectedVoucher->update([
            'voucher_type' => $this->voucher_type,
            'transaction_date' => $this->voucher_date,
            'voucher_number' => $this->reference_no,
            'warehouse_id' => $this->warehouse_id ?: null,
            'kitchen_id' => $this->kitchen_id ?: null,
            'from_storage_location_id' => $this->from_storage_location_id ?: null,
            'to_storage_location_id' => $this->to_storage_location_id ?: null,
            'notes' => $this->notes,
            'updated_by' => auth()->id(),
        ]);

        $this->selectedVoucher->items()->delete();
        foreach ($this->items as $item) {
            $this->selectedVoucher->items()->create([
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'unit_id' => $item['unit_id'],
                'batch_number' => $item['batch_number'] ?: null,
                'expiry_date' => $item['expiry_date'] ?: null,
                'storage_location_id' => $item['storage_location_id'] ?: null,
                'cost_price' => $item['cost_price'] ?: null,
            ]);
        }

        $this->closeModals();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Voucher Updated',
            'message' => "Stock voucher {$this->selectedVoucher->voucher_number} has been updated.",
        ]);
    }

    public function confirmDelete(StockVoucher $voucher): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Delete Voucher',
            'message' => "Are you sure you want to delete voucher {$voucher->voucher_number}? This action cannot be undone.",
            'callback' => "delete({$voucher->id})",
        ]);
    }

    public function delete(StockVoucher $voucher): void
    {
        $ref = $voucher->voucher_number;
        $voucher->delete();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Voucher Deleted',
            'message' => "Voucher {$ref} has been deleted.",
        ]);
    }

    public function postVoucher(StockVoucher $voucher): void
    {
        if ($voucher->status !== 'draft') {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Only draft vouchers can be posted.',
            ]);
            return;
        }

        try {
            $voucher->post();
            $this->dispatch('toast-add', [
                'type' => 'success',
                'title' => 'Voucher Posted',
                'message' => "Voucher {$voucher->voucher_number} has been posted successfully.",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function cancelVoucher(StockVoucher $voucher): void
    {
        if ($voucher->status !== 'posted') {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Only posted vouchers can be cancelled.',
            ]);
            return;
        }

        $voucher->cancel();
        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Voucher Cancelled',
            'message' => "Voucher {$voucher->voucher_number} has been cancelled.",
        ]);
    }

    public function getVouchersProperty()
    {
        return StockVoucher::with(['warehouse', 'kitchen', 'fromStorageLocation', 'toStorageLocation', 'creator', 'items.item', 'items.unit'])
            ->when($this->search, fn($q) => $q->where('voucher_number', 'like', "%{$this->search}%")
                ->orWhere('notes', 'like', "%{$this->search}%"))
            ->when($this->typeFilter, fn($q) => $q->where('voucher_type', $this->typeFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->warehouseFilter, fn($q) => $q->where('warehouse_id', $this->warehouseFilter))
            ->when($this->kitchenFilter, fn($q) => $q->where('kitchen_id', $this->kitchenFilter))
            ->when($this->dateFrom, fn($q) => $q->where('transaction_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->where('transaction_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.stock-vouchers.index', [
            'vouchers' => $this->vouchers,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
            'availableItems' => Item::active()->with('baseUnit')->get(),
            'units' => \App\Models\Unit::active()->get(),
            'storageLocations' => StorageLocation::active()->get(),
        ]);
    }
}
?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Stock Vouchers</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Manage stock movements: additions, issues, returns, transfers, adjustments</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="openCreateModal('addition')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center space-x-2 text-sm">
                <i class="fas fa-plus"></i>
                <span>Stock Addition</span>
            </button>
            <button wire:click="openCreateModal('issue')" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 flex items-center space-x-2 text-sm">
                <i class="fas fa-arrow-right"></i>
                <span>Stock Issue</span>
            </button>
            <button wire:click="openCreateModal('return')" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 flex items-center space-x-2 text-sm">
                <i class="fas fa-undo"></i>
                <span>Stock Return</span>
            </button>
            <button wire:click="openCreateModal('transfer')" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2 text-sm">
                <i class="fas fa-exchange-alt"></i>
                <span>Transfer</span>
            </button>
            <button wire:click="openCreateModal('adjustment')" class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 flex items-center space-x-2 text-sm">
                <i class="fas fa-balance-scale"></i>
                <span>Adjustment</span>
            </button>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search vouchers (reference, notes)..." />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="typeFilter" :options="['' => 'All Types', 'addition' => 'Addition', 'issue' => 'Issue', 'return' => 'Return', 'transfer' => 'Transfer', 'adjustment' => 'Adjustment']" placeholder="Type" />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'draft' => 'Draft', 'posted' => 'Posted', 'cancelled' => 'Cancelled']" placeholder="Status" />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="warehouseFilter" :options="['' => 'All Warehouses'] + $warehouses->pluck('name', 'id')->toArray()" placeholder="Warehouse" searchable />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="kitchenFilter" :options="['' => 'All Kitchens'] + $kitchens->pluck('name', 'id')->toArray()" placeholder="Kitchen" searchable />
            </div>
            <div class="w-full sm:w-40">
                <x-input type="date" wire:model="dateFrom" placeholder="From Date" />
            </div>
            <div class="w-full sm:w-40">
                <x-input type="date" wire:model="dateTo" placeholder="To Date" />
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Reference</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600/50" wire:click="sortBy('transaction_date')">
                            Date <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Warehouse/Kitchen</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Qty</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @foreach($vouchers as $voucher)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900 dark:text-white">{{ $voucher->voucher_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $voucher->transaction_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="['addition' => 'green', 'issue' => 'blue', 'return' => 'amber', 'transfer' => 'purple', 'adjustment' => 'gray'][$voucher->voucher_type] ?? 'gray'">{{ ucfirst($voucher->voucher_type) }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                            @if($voucher->warehouse)
                            <i class="fas fa-warehouse text-slate-400"></i> {{ $voucher->warehouse->name }}
                            @elseif($voucher->kitchen)
                            <i class="fas fa-utensils text-slate-400"></i> {{ $voucher->kitchen->name }}
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $voucher->items->count() }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">
                            {{ number_format($voucher->items->sum('quantity'), 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @php
                                    $status = $voucher->status;
                                    echo match($status) {
                                        'draft' => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                        'posted' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                        'cancelled' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                        default => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                    };
                                @endphp
                            ">{{ ucfirst($voucher->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:click="openViewModal({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-sky-600 dark:hover:text-sky-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($voucher->status === 'draft')
                                <button wire:click="openEditModal({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="postVoucher({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-emerald-600 dark:hover:text-emerald-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Post">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                @elseif($voucher->status === 'posted')
                                <button wire:click="cancelVoucher({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Cancel">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @endif
                                <button wire:click="confirmDelete({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($vouchers->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No vouchers found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-700">
            {{ $vouchers->links() }}
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showCreateModal || $showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-5xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $showCreateModal ? 'Create' : 'Edit' }} {{ ucfirst($voucher_type) }} Voucher</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form wire:submit.prevent="{{ $showCreateModal ? 'create' : 'update' }}" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-plain-select label="Voucher Type" wire:model.live="voucher_type" :options="['addition' => 'Stock Addition', 'issue' => 'Stock Issue', 'return' => 'Stock Return', 'transfer' => 'Transfer', 'adjustment' => 'Adjustment']" required :error="$errors->first('voucher_type')" />
                        <x-input label="Date" wire:model="voucher_date" type="date" required :error="$errors->first('voucher_date')" />
                        <x-input label="Reference No." wire:model="reference_no" placeholder="Auto-generate" :error="$errors->first('reference_no')" />
                        <button type="button" wire:click="generateReferenceNo" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 flex items-center space-x-2 self-end">
                            <i class="fas fa-sync"></i>
                            <span>Generate</span>
                        </button>
                    </div>

                    @if(in_array($voucher_type, ['addition', 'return', 'adjustment']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-plain-select label="Warehouse" wire:model.live="warehouse_id" :options="['' => 'Select Warehouse'] + $warehouses->pluck('name', 'id')->toArray()" searchable :error="$errors->first('warehouse_id')" />
                        <x-plain-select label="Storage Location" wire:model.live="to_storage_location_id" :options="['' => 'Select Location'] + $storageLocations->where('warehouse_id', $warehouse_id)->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                    </div>
                    @elseif($voucher_type === 'issue')
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-plain-select label="Source" wire:model.live="warehouse_id" :options="['' => 'Warehouse'] + $warehouses->pluck('name', 'id')->toArray()" searchable placeholder="From Warehouse" />
                        <x-plain-select label="Kitchen" wire:model.live="kitchen_id" :options="['' => 'Select Kitchen'] + $kitchens->pluck('name', 'id')->toArray()" searchable placeholder="To Kitchen" />
                        <x-plain-select label="From Location" wire:model.live="from_storage_location_id" :options="['' => 'Select Location'] + $storageLocations->where('warehouse_id', $warehouse_id)->pluck('name', 'id')->toArray()" searchable placeholder="Optional" />
                    </div>
                    @elseif($voucher_type === 'transfer')
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-plain-select label="From Warehouse" wire:model.live="warehouse_id" :options="['' => 'Select'] + $warehouses->pluck('name', 'id')->toArray()" searchable />
                        <x-plain-select label="From Location" wire:model.live="from_storage_location_id" :options="['' => 'Select'] + $storageLocations->where('warehouse_id', $warehouse_id)->pluck('name', 'id')->toArray()" searchable />
                        <x-plain-select label="To Warehouse" wire:model.live="kitchen_id" :options="['' => 'Select'] + $warehouses->pluck('name', 'id')->toArray()" searchable />
                        <x-plain-select label="To Location" wire:model.live="to_storage_location_id" :options="['' => 'Select'] + $storageLocations->where('warehouse_id', $kitchen_id)->pluck('name', 'id')->toArray()" searchable />
                    </div>
                    @endif

                    <x-textarea label="Notes" wire:model="notes" rows="2" placeholder="Optional notes..." />

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-medium text-slate-900 dark:text-white">Items</h4>
                            <button type="button" wire:click="addItemRow" class="px-3 py-1 text-sm bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 flex items-center space-x-1">
                                <i class="fas fa-plus"></i>
                                <span>Add Item</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-start text-xs font-medium text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                        <th class="pb-2 w-48">Item</th>
                                        <th class="pb-2 w-24">Qty</th>
                                        <th class="pb-2 w-28">Unit</th>
                                        <th class="pb-2 w-36">Batch #</th>
                                        <th class="pb-2 w-32">Expiry</th>
                                        <th class="pb-2 w-36">Location</th>
                                        <th class="pb-2 w-24">Cost</th>
                                        <th class="pb-2 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                    <tr class="border-b border-slate-100 dark:border-slate-800">
                                        <td class="py-2">
                                            <x-plain-select wire:model.live="items.{{ $index }}.item_id" :options="['' => 'Select Item'] + $availableItems->pluck('name', 'id')->toArray()" searchable class="w-full" :error="$errors->first('items.'.$index.'.item_id')" />
                                        </td>
                                        <td class="py-2">
                                            <x-input wire:model="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" class="w-full" :error="$errors->first('items.'.$index.'.quantity')" />
                                        </td>
                                        <td class="py-2">
                                            <x-plain-select wire:model.live="items.{{ $index }}.unit_id" :options="['' => 'Select'] + $units->pluck('name', 'id')->toArray()" searchable class="w-full" :error="$errors->first('items.'.$index.'.unit_id')" />
                                        </td>
                                        <td class="py-2">
                                            <x-input wire:model="items.{{ $index }}.batch_number" placeholder="Auto or manual" class="w-full" />
                                        </td>
                                        <td class="py-2">
                                            <x-input wire:model="items.{{ $index }}.expiry_date" type="date" class="w-full" />
                                        </td>
                                        <td class="py-2">
                                            <x-plain-select wire:model.live="items.{{ $index }}.storage_location_id" :options="['' => 'Default'] + $storageLocations->pluck('name', 'id')->toArray()" searchable class="w-full" />
                                        </td>
                                        <td class="py-2">
                                            <x-input wire:model="items.{{ $index }}.cost_price" type="number" step="0.01" min="0" class="w-full" />
                                        </td>
                                        <td class="py-2">
                                            @if(count($items) > 1)
                                            <button type="button" wire:click="removeItemRow({{ $index }})" class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400" title="Remove">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sticky bottom-0 -mx-6 -mb-6 flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-700 dark:bg-slate-800">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-500 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{{ $showCreateModal ? 'Create Voucher' : 'Update Voucher' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- View Modal --}}
    @if($showViewModal && $selectedVoucher)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 dark:bg-slate-900 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $selectedVoucher->voucher_number }} - {{ ucfirst($selectedVoucher->voucher_type) }}</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Date</p>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $selectedVoucher->transaction_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Reference</p>
                            <p class="font-mono font-medium text-slate-900 dark:text-white">{{ $selectedVoucher->voucher_number }}</p>
                        </div>
                        <div class="p-4 bg-slate-50/80 dark:bg-slate-800/80 rounded-lg">
                            <p class="text-sm text-slate-500 dark:text-slate-400">Status</p>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @switch($selectedVoucher->status)
                                    @case('draft') bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300 @break
                                    @case('posted') bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 @break
                                    @case('cancelled') bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300 @break
                                    @default bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300
                                @endswitch
                            ">{{ ucfirst($selectedVoucher->status) }}</span>
                        </div>
                    </div>

                    @if($selectedVoucher->warehouse || $selectedVoucher->kitchen || $selectedVoucher->fromStorageLocation || $selectedVoucher->toStorageLocation)
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Locations</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            @if($selectedVoucher->warehouse)
                            <div><span class="text-slate-500 dark:text-slate-400">Warehouse:</span> <span class="font-medium">{{ $selectedVoucher->warehouse->name }}</span></div>
                            @endif
                            @if($selectedVoucher->kitchen)
                            <div><span class="text-slate-500 dark:text-slate-400">Kitchen:</span> <span class="font-medium">{{ $selectedVoucher->kitchen->name }}</span></div>
                            @endif
                            @if($selectedVoucher->fromStorageLocation)
                            <div><span class="text-slate-500 dark:text-slate-400">From Location:</span> <span class="font-medium">{{ $selectedVoucher->fromStorageLocation->name }}</span></div>
                            @endif
                            @if($selectedVoucher->toStorageLocation)
                            <div><span class="text-slate-500 dark:text-slate-400">To Location:</span> <span class="font-medium">{{ $selectedVoucher->toStorageLocation->name }}</span></div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($selectedVoucher->notes)
                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Notes</p>
                        <p class="text-slate-900 dark:text-white">{{ $selectedVoucher->notes }}</p>
                    </div>
                    @endif

                    <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                        <h4 class="font-medium text-slate-900 dark:text-white mb-2">Items ({{ $selectedVoucher->items->count() }})</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-start text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                        <th class="pb-2">Item</th>
                                        <th class="pb-2">Qty</th>
                                        <th class="pb-2">Unit</th>
                                        <th class="pb-2">Batch #</th>
                                        <th class="pb-2">Expiry</th>
                                        <th class="pb-2">Location</th>
                                        <th class="pb-2">Cost</th>
                                        <th class="pb-2">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedVoucher->items as $item)
                                    <tr class="border-b border-slate-100 dark:border-slate-800">
                                        <td class="py-2">{{ $item->item->name }}</td>
                                        <td class="py-2">{{ number_format($item->quantity, 2) }}</td>
                                        <td class="py-2">{{ $item->unit->symbol }}</td>
                                        <td class="py-2 font-mono">{{ $item->batch_number ?? '—' }}</td>
                                        <td class="py-2">{{ $item->expiry_date?->format('M d, Y') ?? '—' }}</td>
                                        <td class="py-2">{{ $item->storageLocation->name ?? '—' }}</td>
                                        <td class="py-2">${{ number_format($item->cost_price ?? 0, 2) }}</td>
                                        <td class="py-2">${{ number_format(($item->cost_price ?? 0) * $item->quantity, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

