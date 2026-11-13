<?php

namespace App\Livewire\StockVouchers;

use App\Models\Item;
use App\Models\Kitchen;
use App\Models\StockVoucher;
use App\Models\StockVoucherItem;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Stock Vouchers')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public string $search = '';

    public string $statusFilter = '';

    public string $warehouseFilter = '';

    public string $kitchenFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showViewModal = false;

    public ?StockVoucher $selectedVoucher = null;

    // Form fields
    public string $voucher_type = 'addition';

    public string $voucher_date = '';

    public string $reference_no = '';

    public string $warehouse_id = '';

    public string $kitchen_id = '';

    public string $from_storage_location_id = '';

    public string $to_storage_location_id = '';

    public string $supplier_id = '';

    public string $notes = '';

    public array $items = [];

    public array $warehouses = [];

    public array $kitchens = [];

    public array $storageLocations = [];

    public array $itemsList = [];

    public array $units = [];

    protected $listeners = ['confirmDelete', 'confirmCancel'];

    public function mount(): void
    {
        $this->voucher_date = now()->toDateString();
        $this->loadReferenceData();
    }

    public function loadReferenceData(): void
    {
        $this->warehouses = Warehouse::active()->get()->toArray();
        $this->kitchens = Kitchen::active()->get()->toArray();
        $this->storageLocations = StorageLocation::active()->with('warehouse')->get()->toArray();
        $this->itemsList = Item::active()->with('baseUnit')->get()->toArray();
        $this->units = Unit::active()->get()->toArray();
    }

    public function updatedWarehouseId(): void
    {
        $this->from_storage_location_id = '';
        $this->to_storage_location_id = '';
    }

    public function updatedKitchenId(): void
    {
        $this->to_storage_location_id = '';
    }

    public function getStorageLocationsProperty(): array
    {
        $warehouseId = $this->warehouse_id ?? $this->kitchen_id;

        if (! $warehouseId) {
            return [];
        }

        return array_filter($this->storageLocations, fn ($loc) => $loc['warehouse_id'] == $warehouseId);
    }

    public function getFilteredItemsProperty(): array
    {
        return $this->itemsList;
    }

    public function openCreateModal(string $type = 'addition'): void
    {
        $this->resetForm();
        $this->voucher_type = $type;
        $this->voucher_date = now()->toDateString();
        $this->generateReferenceNo();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetForm();
        $this->selectedVoucher = StockVoucher::with(['items.item', 'items.unit', 'warehouse', 'kitchen'])->findOrFail($id);

        if (! $this->selectedVoucher->canBeEdited()) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cannot Edit',
                'message' => 'Only draft vouchers can be edited.',
            ])->to(window);

            return;
        }

        $this->voucher_type = $this->selectedVoucher->voucher_type;
        $this->voucher_date = $this->selectedVoucher->transaction_date->format('Y-m-d');
        $this->reference_no = $this->selectedVoucher->voucher_number;
        $this->warehouse_id = (string) $this->selectedVoucher->warehouse_id;
        $this->kitchen_id = (string) $this->selectedVoucher->kitchen_id;
        $this->from_storage_location_id = (string) $this->selectedVoucher->from_storage_location_id;
        $this->to_storage_location_id = (string) $this->selectedVoucher->to_storage_location_id;
        $this->supplier_id = (string) $this->selectedVoucher->supplier_id;
        $this->notes = $this->selectedVoucher->notes;

        $this->items = $this->selectedVoucher->items->map(function ($item) {
            return [
                'id' => $item->id,
                'item_id' => (string) $item->item_id,
                'batch_id' => $item->batch_id ? (string) $item->batch_id : '',
                'batch_number' => $item->batch_number ?? '',
                'expiry_date' => $item->expiry_date?->format('Y-m-d') ?? '',
                'storage_location_id' => $item->from_storage_location_id ? (string) $item->from_storage_location_id : ($item->to_storage_location_id ? (string) $item->to_storage_location_id : ''),
                'quantity' => (string) $item->quantity,
                'unit_id' => (string) $item->unit_id,
                'cost_price' => (string) $item->unit_price,
                'notes' => $item->notes ?? '',
                'adjustment_type' => $item->adjustment_type ?? '',
            ];
        })->toArray();

        if (empty($this->items)) {
            $this->addItemRow();
        }

        $this->showEditModal = true;
    }

    public function openViewModal(int $id): void
    {
        $this->selectedVoucher = StockVoucher::with(['items.item', 'items.unit', 'warehouse', 'kitchen', 'fromStorageLocation', 'toStorageLocation', 'creator', 'approver'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showViewModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->voucher_type = 'addition';
        $this->voucher_date = now()->toDateString();
        $this->reference_no = '';
        $this->warehouse_id = '';
        $this->kitchen_id = '';
        $this->from_storage_location_id = '';
        $this->to_storage_location_id = '';
        $this->supplier_id = '';
        $this->notes = '';
        $this->items = [];
        $this->selectedVoucher = null;
        $this->resetValidation();
    }

    public function generateReferenceNo(): void
    {
        $service = new StockService;
        $this->reference_no = $service->generateVoucherNumber($this->voucher_type);
    }

    public function addItemRow(): void
    {
        $this->items[] = [
            'id' => null,
            'item_id' => '',
            'batch_id' => '',
            'batch_number' => '',
            'expiry_date' => '',
            'storage_location_id' => '',
            'quantity' => '',
            'unit_id' => '',
            'cost_price' => '',
            'notes' => '',
        ];
    }

    public function removeItemRow(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        $rules = [
            'voucher_type' => 'required|in:addition,issue,return,transfer,adjustment',
            'voucher_date' => 'required|date',
            'reference_no' => 'required|string|max:50|unique:stock_vouchers,voucher_number',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
        ];

        // Location rules based on voucher type
        if (in_array($this->voucher_type, ['addition', 'return', 'adjustment'])) {
            $rules['warehouse_id'] = 'required|exists:warehouses,id';
            $rules['to_storage_location_id'] = 'nullable|exists:storage_locations,id';
        } elseif ($this->voucher_type === 'issue') {
            $rules['warehouse_id'] = 'required|exists:warehouses,id';
            $rules['kitchen_id'] = 'required|exists:kitchens,id';
            $rules['from_storage_location_id'] = 'nullable|exists:storage_locations,id';
        } elseif ($this->voucher_type === 'transfer') {
            $rules['warehouse_id'] = 'required|exists:warehouses,id';
            $rules['from_storage_location_id'] = 'required|exists:storage_locations,id';
            $rules['kitchen_id'] = 'required|exists:warehouses,id'; // to warehouse
            $rules['to_storage_location_id'] = 'required|exists:storage_locations,id';
        }

        // Item rules
        foreach ($this->items as $index => $item) {
            $rules["items.{$index}.item_id"] = 'required|exists:items,id';
            $rules["items.{$index}.quantity"] = 'required|numeric|min:0.01';
            $rules["items.{$index}.unit_id"] = 'required|exists:units,id';
            $rules["items.{$index}.cost_price"] = 'nullable|numeric|min:0';
            $rules["items.{$index}.storage_location_id"] = 'nullable|exists:storage_locations,id';
            $rules["items.{$index}.expiry_date"] = 'nullable|date';
            if ($this->voucher_type === 'adjustment') {
                $rules["items.{$index}.adjustment_type"] = 'required|in:positive,negative';
            }
        }

        // For edit mode, allow same voucher_number
        if ($this->showEditModal && $this->selectedVoucher) {
            $rules['reference_no'] = 'required|string|max:50|unique:stock_vouchers,voucher_number,'.$this->selectedVoucher->id;
        }

        return $rules;
    }

    public function create(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                $voucher = StockVoucher::create([
                    'voucher_number' => $this->reference_no,
                    'voucher_type' => $this->voucher_type,
                    'transaction_date' => $this->voucher_date,
                    'warehouse_id' => $this->warehouse_id ?: null,
                    'from_storage_location_id' => $this->from_storage_location_id ?: null,
                    'to_storage_location_id' => $this->to_storage_location_id ?: null,
                    'kitchen_id' => $this->kitchen_id ?: null,
                    'supplier_id' => $this->supplier_id ?: null,
                    'notes' => $this->notes,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ]);

                foreach ($this->items as $itemData) {
                    $item = Item::find($itemData['item_id']);
                    $unit = Unit::find($itemData['unit_id']);
                    $conversionFactor = $unit->conversion_factor ?? 1;
                    $quantityBase = $itemData['quantity'] * $conversionFactor;

                    StockVoucherItem::create([
                        'voucher_id' => $voucher->id,
                        'item_id' => $itemData['item_id'],
                        'batch_id' => $itemData['batch_id'] ?: null,
                        'batch_number' => $itemData['batch_number'] ?: null,
                        'expiry_date' => $itemData['expiry_date'] ?: null,
                        'unit_id' => $itemData['unit_id'],
                        'quantity' => $itemData['quantity'],
                        'conversion_factor' => $conversionFactor,
                        'quantity_base' => $quantityBase,
                        'unit_price' => $itemData['cost_price'] ?: 0,
                        'total_cost' => $quantityBase * ($itemData['cost_price'] ?: 0),
                        'from_storage_location_id' => in_array($this->voucher_type, ['issue', 'transfer']) ? $itemData['storage_location_id'] ?: null : null,
                        'to_storage_location_id' => in_array($this->voucher_type, ['addition', 'return', 'transfer']) ? $itemData['storage_location_id'] ?: null : null,
                        'notes' => $itemData['notes'] ?? null,
                        'adjustment_type' => $itemData['adjustment_type'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }

                // Auto-post immediately (default behavior)
                $service = new StockService;
                $service->processVoucher($voucher);

                $this->dispatch('toast-add', [
                    'type' => 'success',
                    'title' => 'Voucher Created & Posted',
                    'message' => "Voucher {$voucher->voucher_number} has been created and posted successfully. Stock has been updated.",
                ])->to(window);
            });

            $this->closeModals();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Creation Failed',
                'message' => $e->getMessage(),
            ])->to(window);
        }
    }

    public function saveAsDraft(): void
    {
        $this->validate();

        try {
            DB::transaction(function () {
                if ($this->showEditModal && $this->selectedVoucher) {
                    $voucher = $this->selectedVoucher;
                    $voucher->update([
                        'voucher_number' => $this->reference_no,
                        'voucher_type' => $this->voucher_type,
                        'transaction_date' => $this->voucher_date,
                        'warehouse_id' => $this->warehouse_id ?: null,
                        'from_storage_location_id' => $this->from_storage_location_id ?: null,
                        'to_storage_location_id' => $this->to_storage_location_id ?: null,
                        'kitchen_id' => $this->kitchen_id ?: null,
                        'supplier_id' => $this->supplier_id ?: null,
                        'notes' => $this->notes,
                        'updated_by' => Auth::id(),
                    ]);

                    // Delete existing items and recreate
                    $voucher->items()->delete();
                } else {
                    $voucher = StockVoucher::create([
                        'voucher_number' => $this->reference_no,
                        'voucher_type' => $this->voucher_type,
                        'transaction_date' => $this->voucher_date,
                        'warehouse_id' => $this->warehouse_id ?: null,
                        'from_storage_location_id' => $this->from_storage_location_id ?: null,
                        'to_storage_location_id' => $this->to_storage_location_id ?: null,
                        'kitchen_id' => $this->kitchen_id ?: null,
                        'supplier_id' => $this->supplier_id ?: null,
                        'notes' => $this->notes,
                        'status' => 'draft',
                        'created_by' => Auth::id(),
                    ]);
                }

                foreach ($this->items as $itemData) {
                    $item = Item::find($itemData['item_id']);
                    $unit = Unit::find($itemData['unit_id']);
                    $conversionFactor = $unit->conversion_factor ?? 1;
                    $quantityBase = $itemData['quantity'] * $conversionFactor;

                    StockVoucherItem::create([
                        'voucher_id' => $voucher->id,
                        'item_id' => $itemData['item_id'],
                        'batch_id' => $itemData['batch_id'] ?: null,
                        'batch_number' => $itemData['batch_number'] ?: null,
                        'expiry_date' => $itemData['expiry_date'] ?: null,
                        'unit_id' => $itemData['unit_id'],
                        'quantity' => $itemData['quantity'],
                        'conversion_factor' => $conversionFactor,
                        'quantity_base' => $quantityBase,
                        'unit_price' => $itemData['cost_price'] ?: 0,
                        'total_cost' => $quantityBase * ($itemData['cost_price'] ?: 0),
                        'from_storage_location_id' => in_array($this->voucher_type, ['issue', 'transfer']) ? $itemData['storage_location_id'] ?: null : null,
                        'to_storage_location_id' => in_array($this->voucher_type, ['addition', 'return', 'transfer']) ? $itemData['storage_location_id'] ?: null : null,
                        'notes' => $itemData['notes'] ?? null,
                        'adjustment_type' => $itemData['adjustment_type'] ?? null,
                        'created_by' => Auth::id(),
                    ]);
                }

                $this->dispatch('toast-add', [
                    'type' => 'success',
                    'title' => 'Voucher Saved as Draft',
                    'message' => "Voucher {$voucher->voucher_number} has been saved as draft.",
                ])->to(window);
            });

            $this->closeModals();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Save Failed',
                'message' => $e->getMessage(),
            ])->to(window);
        }
    }

    public function update(): void
    {
        $this->saveAsDraft();
    }

    public function postVoucher(int $id): void
    {
        $voucher = StockVoucher::with('items.item')->findOrFail($id);

        if (! $voucher->canBeEdited()) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cannot Post',
                'message' => 'Only draft vouchers can be posted.',
            ])->to(window);

            return;
        }

        try {
            DB::transaction(function () use ($voucher) {
                $service = new StockService;
                $service->processVoucher($voucher);
            });

            $this->dispatch('toast-add', [
                'type' => 'success',
                'title' => 'Voucher Posted',
                'message' => "Voucher {$voucher->voucher_number} has been posted successfully. Stock has been updated.",
            ])->to(window);

            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Posting Failed',
                'message' => $e->getMessage(),
            ])->to(window);
        }
    }

    public function confirmCancel(int $id): void
    {
        $this->dispatch('confirm-modal', [
            'title' => 'Cancel Voucher',
            'message' => 'Are you sure you want to cancel this voucher? This will reverse all stock movements.',
            'callback' => "cancelVoucher({$id})",
        ]);
    }

    public function cancelVoucher(int $id): void
    {
        $voucher = StockVoucher::findOrFail($id);

        if ($voucher->status !== 'approved') {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cannot Cancel',
                'message' => 'Only approved vouchers can be cancelled.',
            ])->to(window);

            return;
        }

        try {
            DB::transaction(function () use ($voucher) {
                // Reverse stock movements
                $movements = $voucher->movements;
                foreach ($movements as $movement) {
                    if ($movement->batch_id) {
                        $batch = $movement->batch;
                        if ($batch) {
                            if ($movement->direction === 'in') {
                                $batch->deductQuantity($movement->quantity_base);
                            } else {
                                $batch->addQuantity($movement->quantity_base);
                            }
                        }
                    }
                    $movement->delete();
                }

                $voucher->update([
                    'status' => 'cancelled',
                    'updated_by' => Auth::id(),
                ]);
            });

            $this->dispatch('toast-add', [
                'type' => 'success',
                'title' => 'Voucher Cancelled',
                'message' => "Voucher {$voucher->voucher_number} has been cancelled. Stock movements reversed.",
            ])->to(window);

            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cancellation Failed',
                'message' => $e->getMessage(),
            ])->to(window);
        }
    }

    public function confirmDelete(int $id): void
    {
        $voucher = StockVoucher::findOrFail($id);

        if (! $voucher->canBeEdited()) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cannot Delete',
                'message' => 'Only draft vouchers can be deleted.',
            ])->to(window);

            return;
        }

        $this->dispatch('confirm-modal', [
            'title' => 'Delete Voucher',
            'message' => "Are you sure you want to delete voucher {$voucher->voucher_number}? This action cannot be undone.",
            'callback' => "deleteVoucher({$id})",
        ]);
    }

    public function deleteVoucher(int $id): void
    {
        $voucher = StockVoucher::findOrFail($id);

        if (! $voucher->canBeEdited()) {
            $this->dispatch('toast-add', [
                'type' => 'error',
                'title' => 'Cannot Delete',
                'message' => 'Only draft vouchers can be deleted.',
            ])->to(window);

            return;
        }

        $voucherNumber = $voucher->voucher_number;
        $voucher->delete();

        $this->dispatch('toast-add', [
            'type' => 'success',
            'title' => 'Voucher Deleted',
            'message' => "Voucher {$voucherNumber} has been deleted.",
        ])->to(window);

        $this->resetPage();
    }

    public function render()
    {
        $allowedTypes = ['addition', 'issue', 'return', 'transfer', 'adjustment'];

        if (! in_array($this->typeFilter, $allowedTypes, true)) {
            $this->typeFilter = '';
        }

        $vouchers = StockVoucher::with(['warehouse', 'kitchen', 'items.item', 'items.unit'])
            ->when($this->typeFilter, fn ($query) => $query->where('voucher_type', $this->typeFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->warehouseFilter, fn ($query) => $query->where('warehouse_id', $this->warehouseFilter))
            ->when($this->kitchenFilter, fn ($query) => $query->where('kitchen_id', $this->kitchenFilter))
            ->when($this->dateFrom, fn ($query) => $query->where('transaction_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->where('transaction_date', '<=', $this->dateTo))
            ->when($this->search, fn ($query) => $query->where('voucher_number', 'like', "%{$this->search}%"))
            ->latest('transaction_date')
            ->paginate(15);

        return view('livewire.stock-vouchers.index', [
            'vouchers' => $vouchers,
            'warehouses' => Warehouse::active()->get(),
            'kitchens' => Kitchen::active()->get(),
        ]);
    }
}
