<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Stock Vouchers</h1>
            <p class="text-slate-500 mt-1">Manage stock movements: additions, issues, returns, transfers, adjustments</p>
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

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <x-input wire:model.debounce.300ms="search" placeholder="Search vouchers (reference, notes)..." />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="typeFilter" :options="['' => 'All Types', 'addition' => 'Addition', 'issue' => 'Issue', 'return' => 'Return', 'transfer' => 'Transfer', 'adjustment' => 'Adjustment']" placeholder="Type" />
            </div>
            <div class="w-full sm:w-40">
                <x-plain-select wire:model.live="statusFilter" :options="['' => 'All Status', 'draft' => 'Draft', 'approved' => 'Approved', 'cancelled' => 'Cancelled']" placeholder="Status" />
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

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Reference</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider cursor-pointer hover:bg-slate-100" wire:click="sortBy('transaction_date')">
                            Date <i class="fas fa-sort text-xs ms-1"></i>
                        </th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Warehouse/Kitchen</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Qty</th>
                        <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3.5 text-end text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($vouchers as $voucher)
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 text-sm font-mono font-medium text-slate-900">{{ $voucher->voucher_number }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $voucher->transaction_date?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="['addition' => 'green', 'issue' => 'blue', 'return' => 'amber', 'transfer' => 'purple', 'adjustment' => 'gray'][$voucher->voucher_type] ?? 'gray'">{{ ucfirst($voucher->voucher_type) }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">
                            @if($voucher->warehouse)
                            <i class="fas fa-warehouse text-slate-400"></i> {{ $voucher->warehouse->name }}
                            @elseif($voucher->kitchen)
                            <i class="fas fa-utensils text-slate-400"></i> {{ $voucher->kitchen->name }}
                            @else
                            <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $voucher->items->count() }}</td>
                        <td class="px-4 py-3 text-sm font-mono text-slate-900">
                            {{ number_format($voucher->items->sum('quantity'), 2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @php
                                    $status = $voucher->status;
                                    echo match($status) {
                                        'draft' => 'bg-slate-100 text-slate-800',
                                        'approved' => 'bg-emerald-50 text-emerald-700',
                                        'cancelled' => 'bg-rose-50 text-rose-700',
                                        default => 'bg-slate-100 text-slate-800',
                                    };
                                @endphp
                            ">{{ ucfirst($voucher->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end space-x-2">
                                <button wire:click="openViewModal({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-sky-600 rounded-lg hover:bg-slate-100" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($voucher->status === 'draft')
                                <button wire:click="openEditModal({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg hover:bg-slate-100" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="postVoucher({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-emerald-600 rounded-lg hover:bg-slate-100" title="Post">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                @elseif($voucher->status === 'approved')
                                <button wire:click="confirmCancel({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-slate-100" title="Cancel">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @endif
                                <button wire:click="confirmDelete({{ $voucher->id }})" class="p-2 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-slate-100" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if($vouchers->isEmpty())
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-500">No vouchers found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-200">
            {{ $vouchers->links() }}
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showCreateModal || $showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 p-4 backdrop-blur-sm" wire:ignore.self>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="true" x-transition class="relative w-full max-w-5xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $showCreateModal ? 'Create' : 'Edit' }} {{ ucfirst($voucher_type) }} Voucher</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <form class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <x-plain-select label="Voucher Type" wire:model.live="voucher_type" :options="['addition' => 'Stock Addition', 'issue' => 'Stock Issue', 'return' => 'Stock Return', 'transfer' => 'Transfer', 'adjustment' => 'Adjustment']" required :error="$errors->first('voucher_type')" />
                        <x-input label="Date" wire:model="voucher_date" type="date" required :error="$errors->first('voucher_date')" />
                        <x-input label="Reference No." wire:model="reference_no" placeholder="Auto-generate" :error="$errors->first('reference_no')" />
                        <button type="button" wire:click="generateReferenceNo" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 flex items-center space-x-2 self-end">
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

                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-medium text-slate-900">Items</h4>
                            <button type="button" wire:click="addItemRow" class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 flex items-center space-x-1">
                                <i class="fas fa-plus"></i>
                                <span>Add Item</span>
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-start text-xs font-medium text-slate-500 border-b border-slate-200">
                                        <th class="pb-2 w-48">Item</th>
                                        <th class="pb-2 w-24">Qty</th>
                                        <th class="pb-2 w-28">Unit</th>
                                        <th class="pb-2 w-36">Batch #</th>
                                        <th class="pb-2 w-32">Expiry</th>
                                        <th class="pb-2 w-36">Location</th>
                                        <th class="pb-2 w-24">Cost</th>
                                        <th class="pb-2 w-32">Adj. Type</th>
                                        <th class="pb-2 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                    <tr class="border-b border-slate-100">
                                        <td class="py-2">
                                            <x-plain-select wire:model.live="items.{{ $index }}.item_id" :options="['' => 'Select Item'] + $items->pluck('name', 'id')->toArray()" searchable class="w-full" :error="$errors->first('items.'.$index.'.item_id')" />
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
                                            @if($voucher_type === 'adjustment')
                                            <x-plain-select wire:model.live="items.{{ $index }}.adjustment_type" :options="['positive' => 'Positive (Add)', 'negative' => 'Negative (Waste)']" class="w-full" />
                                            @else
                                            <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            @if(count($items) > 1)
                                            <button type="button" wire:click="removeItemRow({{ $index }})" class="p-1 text-slate-400 hover:text-rose-600" title="Remove">
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

                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200">
                        <button type="button" wire:click="closeModals" class="px-4 py-2 text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50/60">Cancel</button>
                        @if($showCreateModal)
                            <button type="button" wire:click="saveAsDraft" class="px-4 py-2 bg-slate-100 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-200">Save as Draft</button>
                            <button type="submit" wire:click="create" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center space-x-2">
                                <i class="fas fa-check-circle"></i>
                                <span>Save & Post Immediately</span>
                            </button>
                        @else
                            <button type="button" wire:click="saveAsDraft" class="px-4 py-2 bg-slate-100 text-slate-700 border border-slate-300 rounded-lg hover:bg-slate-200">Save as Draft</button>
                            <button type="submit" wire:click="update" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Voucher</button>
                        @endif
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
            <div x-show="true" x-transition class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $selectedVoucher->voucher_number }} - {{ ucfirst($selectedVoucher->voucher_type) }}</h3>
                    <button wire:click="closeModals" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times text-xl"></i></button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-sm text-slate-500">Date</p>
                            <p class="font-medium text-slate-900">{{ $selectedVoucher->transaction_date?->format('M d, Y') ?? '—' }}</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-sm text-slate-500">Reference</p>
                            <p class="font-mono font-medium text-slate-900">{{ $selectedVoucher->voucher_number }}</p>
                        </div>
                        <div class="p-4 bg-slate-50 rounded-lg">
                            <p class="text-sm text-slate-500">Status</p>
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                @switch($selectedVoucher->status)
                                    @case('draft') bg-slate-100 text-slate-800 @break
                                    @case('posted') bg-emerald-50 text-emerald-700 @break
                                    @case('cancelled') bg-rose-50 text-rose-700 @break
                                    @default bg-slate-100 text-slate-800
                                @endswitch
                            ">{{ ucfirst($selectedVoucher->status) }}</span>
                        </div>
                    </div>

                    @if($selectedVoucher->warehouse || $selectedVoucher->kitchen || $selectedVoucher->fromStorageLocation || $selectedVoucher->toStorageLocation)
                    <div class="border-t border-slate-200 pt-4">
                        <h4 class="font-medium text-slate-900 mb-2">Locations</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            @if($selectedVoucher->warehouse)
                            <div><span class="text-slate-500">Warehouse:</span> <span class="font-medium">{{ $selectedVoucher->warehouse->name }}</span></div>
                            @endif
                            @if($selectedVoucher->kitchen)
                            <div><span class="text-slate-500">Kitchen:</span> <span class="font-medium">{{ $selectedVoucher->kitchen->name }}</span></div>
                            @endif
                            @if($selectedVoucher->fromStorageLocation)
                            <div><span class="text-slate-500">From Location:</span> <span class="font-medium">{{ $selectedVoucher->fromStorageLocation->name }}</span></div>
                            @endif
                            @if($selectedVoucher->toStorageLocation)
                            <div><span class="text-slate-500">To Location:</span> <span class="font-medium">{{ $selectedVoucher->toStorageLocation->name }}</span></div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($selectedVoucher->notes)
                    <div class="border-t border-slate-200 pt-4">
                        <p class="text-sm text-slate-500">Notes</p>
                        <p class="text-slate-900">{{ $selectedVoucher->notes }}</p>
                    </div>
                    @endif

                    <div class="border-t border-slate-200 pt-4">
                        <h4 class="font-medium text-slate-900 mb-2">Items ({{ $selectedVoucher->items->count() }})</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-start text-slate-500 border-b border-slate-200">
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
                                    <tr class="border-b border-slate-100">
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