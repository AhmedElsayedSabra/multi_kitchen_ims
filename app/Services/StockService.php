<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\StockMovement;
use App\Models\StockVoucher;
use App\Models\StockVoucherItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockService
{
    /**
     * Process a stock voucher and create stock movements.
     */
    public function processVoucher(StockVoucher $voucher): void
    {
        DB::transaction(function () use ($voucher) {
            foreach ($voucher->items as $item) {
                $this->processVoucherItem($voucher, $item);
            }

            $voucher->update(['status' => 'approved', 'approved_at' => now()]);
        });
    }

    /**
     * Process a single voucher item and create stock movements.
     */
    protected function processVoucherItem(StockVoucher $voucher, StockVoucherItem $voucherItem): void
    {
        $item = $voucherItem->item;
        $quantityBase = $voucherItem->quantity_base;
        $unitPrice = $voucherItem->unit_price;
        $totalCost = $voucherItem->total_cost;
        $performedBy = $voucher->created_by;

        switch ($voucher->voucher_type) {
            case 'addition':
                $this->processAddition($voucher, $voucherItem, $quantityBase, $unitPrice, $totalCost, $performedBy);
                break;
            case 'issue':
                $this->processIssue($voucher, $voucherItem, $quantityBase, $unitPrice, $totalCost, $performedBy);
                break;
            case 'return':
                $this->processReturn($voucher, $voucherItem, $quantityBase, $unitPrice, $totalCost, $performedBy);
                break;
            case 'transfer':
                $this->processTransfer($voucher, $voucherItem, $quantityBase, $unitPrice, $totalCost, $performedBy);
                break;
            case 'adjustment':
                $this->processAdjustment($voucher, $voucherItem, $quantityBase, $unitPrice, $totalCost, $performedBy);
                break;
        }
    }

    /**
     * Process stock addition (supply/receiving).
     */
    protected function processAddition(
        StockVoucher $voucher,
        StockVoucherItem $voucherItem,
        float $quantityBase,
        float $unitPrice,
        float $totalCost,
        int $performedBy
    ): void {
        $storageLocation = $voucherItem->to_storage_location_id
            ? $voucherItem->toStorageLocation
            : $voucher->warehouse?->storageLocations()->first();

        // Create or get batch
        $batch = $this->getOrCreateBatch($voucherItem, $storageLocation);

        // Create stock movement (IN)
        $runningBalance = $this->getRunningBalance($voucherItem->item_id, $storageLocation?->id) + $quantityBase;

        StockMovement::create([
            'voucher_id' => $voucher->id,
            'voucher_item_id' => $voucherItem->id,
            'item_id' => $voucherItem->item_id,
            'batch_id' => $batch->id,
            'movement_type' => 'in',
            'direction' => 'in',
            'warehouse_id' => $voucher->warehouse_id,
            'storage_location_id' => $storageLocation?->id,
            'kitchen_id' => $voucher->kitchen_id,
            'unit_id' => $voucherItem->unit_id,
            'quantity' => $voucherItem->quantity,
            'conversion_factor' => $voucherItem->conversion_factor,
            'quantity_base' => $quantityBase,
            'unit_price' => $unitPrice,
            'total_cost' => $totalCost,
            'running_balance_base' => $runningBalance,
            'performed_by' => $performedBy,
            'movement_date' => $voucher->transaction_date,
            'notes' => $voucherItem->notes,
        ]);

        // Update batch quantity
        $batch->addQuantity($quantityBase);
    }

    /**
     * Process stock issue (withdrawal to kitchen).
     */
    protected function processIssue(
        StockVoucher $voucher,
        StockVoucherItem $voucherItem,
        float $quantityBase,
        float $unitPrice,
        float $totalCost,
        int $performedBy
    ): void {
        $fromLocation = $voucherItem->from_storage_location_id
            ? $voucherItem->fromStorageLocation
            : $voucher->warehouse?->storageLocations()->first();

        // Use FEFO to select batches (fallback to warehouse-wide then global)
        $batches = $this->selectBatchesFefo($voucherItem->item_id, $quantityBase, $fromLocation?->id, $voucher->warehouse_id);

        $remainingQty = $quantityBase;
        foreach ($batches as $batch) {
            $deductQty = min($remainingQty, $batch->quantity_remaining);

            $runningBalance = $this->getRunningBalance($voucherItem->item_id, $fromLocation?->id) - $deductQty;

            StockMovement::create([
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $voucherItem->id,
                'item_id' => $voucherItem->item_id,
                'batch_id' => $batch->id,
                'movement_type' => 'out',
                'direction' => 'out',
                'warehouse_id' => $voucher->warehouse_id,
                'storage_location_id' => $fromLocation?->id,
                'kitchen_id' => $voucher->kitchen_id,
                'unit_id' => $voucherItem->unit_id,
                'quantity' => $voucherItem->unit->fromBaseUnit($deductQty),
                'conversion_factor' => $voucherItem->conversion_factor,
                'quantity_base' => $deductQty,
                'unit_price' => $batch->unit_cost ?: $unitPrice,
                'total_cost' => $deductQty * ($batch->unit_cost ?: $unitPrice),
                'running_balance_base' => $runningBalance,
                'performed_by' => $performedBy,
                'movement_date' => $voucher->transaction_date,
                'notes' => $voucherItem->notes,
            ]);

            $batch->deductQuantity($deductQty);
            $remainingQty -= $deductQty;

            if ($remainingQty <= 0) {
                break;
            }
        }

        if ($remainingQty > 0) {
            throw new \Exception("Insufficient stock for item {$voucherItem->item->name}. Short by {$remainingQty} base units.");
        }
    }

    /**
     * Process stock return (from kitchen or to supplier).
     */
    protected function processReturn(
        StockVoucher $voucher,
        StockVoucherItem $voucherItem,
        float $quantityBase,
        float $unitPrice,
        float $totalCost,
        int $performedBy
    ): void {
        $toLocation = $voucherItem->to_storage_location_id
            ? $voucherItem->toStorageLocation
            : $voucher->warehouse?->storageLocations()->first();

        // If returning to supplier, no batch tracking needed
        // If returning from kitchen, we need to add back to warehouse
        if ($voucher->supplier_id) {
            // Return to supplier - just record movement out
            $fromLocation = $voucherItem->from_storage_location_id
                ? $voucherItem->fromStorageLocation
                : $voucher->warehouse?->storageLocations()->first();

            $batches = $this->selectBatchesFefo($voucherItem->item_id, $quantityBase, $fromLocation?->id, $voucher->warehouse_id);

            $remainingQty = $quantityBase;
            foreach ($batches as $batch) {
                $deductQty = min($remainingQty, $batch->quantity_remaining);

                $runningBalance = $this->getRunningBalance($voucherItem->item_id, $fromLocation?->id) - $deductQty;

                StockMovement::create([
                    'voucher_id' => $voucher->id,
                    'voucher_item_id' => $voucherItem->id,
                    'item_id' => $voucherItem->item_id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'out',
                    'direction' => 'out',
                    'warehouse_id' => $voucher->warehouse_id,
                    'storage_location_id' => $fromLocation?->id,
                    'kitchen_id' => null,
                    'unit_id' => $voucherItem->unit_id,
                    'quantity' => $voucherItem->unit->fromBaseUnit($deductQty),
                    'conversion_factor' => $voucherItem->conversion_factor,
                    'quantity_base' => $deductQty,
                    'unit_price' => $batch->unit_cost ?: $unitPrice,
                    'total_cost' => $deductQty * ($batch->unit_cost ?: $unitPrice),
                    'running_balance_base' => $runningBalance,
                    'performed_by' => $performedBy,
                    'movement_date' => $voucher->transaction_date,
                    'notes' => 'Return to supplier: '.($voucherItem->notes ?? ''),
                ]);

                $batch->deductQuantity($deductQty);
                $remainingQty -= $deductQty;

                if ($remainingQty <= 0) {
                    break;
                }
            }
        } else {
            // Return from kitchen to warehouse - add back to stock
            $batch = $this->getOrCreateBatch($voucherItem, $toLocation);

            $runningBalance = $this->getRunningBalance($voucherItem->item_id, $toLocation?->id) + $quantityBase;

            StockMovement::create([
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $voucherItem->id,
                'item_id' => $voucherItem->item_id,
                'batch_id' => $batch->id,
                'movement_type' => 'in',
                'direction' => 'in',
                'warehouse_id' => $voucher->warehouse_id,
                'storage_location_id' => $toLocation?->id,
                'kitchen_id' => $voucher->kitchen_id,
                'unit_id' => $voucherItem->unit_id,
                'quantity' => $voucherItem->quantity,
                'conversion_factor' => $voucherItem->conversion_factor,
                'quantity_base' => $quantityBase,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'running_balance_base' => $runningBalance,
                'performed_by' => $performedBy,
                'movement_date' => $voucher->transaction_date,
                'notes' => 'Return from kitchen: '.($voucherItem->notes ?? ''),
            ]);

            $batch->addQuantity($quantityBase);
        }
    }

    /**
     * Process internal transfer between locations/kitchens.
     */
    protected function processTransfer(
        StockVoucher $voucher,
        StockVoucherItem $voucherItem,
        float $quantityBase,
        float $unitPrice,
        float $totalCost,
        int $performedBy
    ): void {
        $fromLocation = $voucherItem->fromStorageLocation;
        $toLocation = $voucherItem->toStorageLocation;

        // Out from source location
        $batches = $this->selectBatchesFefo($voucherItem->item_id, $quantityBase, $fromLocation?->id, $voucher->warehouse_id);

        $remainingQty = $quantityBase;
        foreach ($batches as $batch) {
            $deductQty = min($remainingQty, $batch->quantity_remaining);

            $runningBalance = $this->getRunningBalance($voucherItem->item_id, $fromLocation?->id) - $deductQty;

            StockMovement::create([
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $voucherItem->id,
                'item_id' => $voucherItem->item_id,
                'batch_id' => $batch->id,
                'movement_type' => 'transfer',
                'direction' => 'out',
                'warehouse_id' => $fromLocation?->warehouse_id,
                'storage_location_id' => $fromLocation?->id,
                'kitchen_id' => null,
                'unit_id' => $voucherItem->unit_id,
                'quantity' => $voucherItem->unit->fromBaseUnit($deductQty),
                'conversion_factor' => $voucherItem->conversion_factor,
                'quantity_base' => $deductQty,
                'unit_price' => $batch->unit_cost ?: $unitPrice,
                'total_cost' => $deductQty * ($batch->unit_cost ?: $unitPrice),
                'running_balance_base' => $runningBalance,
                'performed_by' => $performedBy,
                'movement_date' => $voucher->transaction_date,
                'notes' => 'Transfer out to '.($toLocation?->name ?? 'unknown'),
            ]);

            $batch->deductQuantity($deductQty);
            $remainingQty -= $deductQty;

            if ($remainingQty <= 0) {
                break;
            }
        }

        if ($remainingQty > 0) {
            throw new \Exception("Insufficient stock for transfer. Short by {$remainingQty} base units.");
        }

        // In to destination location
        $toBatch = $this->getOrCreateBatch($voucherItem, $toLocation, $batches[0] ?? null);

        $runningBalance = $this->getRunningBalance($voucherItem->item_id, $toLocation?->id) + $quantityBase;

        StockMovement::create([
            'voucher_id' => $voucher->id,
            'voucher_item_id' => $voucherItem->id,
            'item_id' => $voucherItem->item_id,
            'batch_id' => $toBatch->id,
            'movement_type' => 'transfer',
            'direction' => 'in',
            'warehouse_id' => $toLocation?->warehouse_id,
            'storage_location_id' => $toLocation?->id,
            'kitchen_id' => null,
            'unit_id' => $voucherItem->unit_id,
            'quantity' => $voucherItem->quantity,
            'conversion_factor' => $voucherItem->conversion_factor,
            'quantity_base' => $quantityBase,
            'unit_price' => $unitPrice,
            'total_cost' => $totalCost,
            'running_balance_base' => $runningBalance,
            'performed_by' => $performedBy,
            'movement_date' => $voucher->transaction_date,
            'notes' => 'Transfer in from '.($fromLocation?->name ?? 'unknown'),
        ]);

        $toBatch->addQuantity($quantityBase);
    }

    /**
     * Process stock adjustment (waste/audit correction).
     */
    protected function processAdjustment(
        StockVoucher $voucher,
        StockVoucherItem $voucherItem,
        float $quantityBase,
        float $unitPrice,
        float $totalCost,
        int $performedBy
    ): void {
        $storageLocation = $voucherItem->from_storage_location_id
            ? $voucherItem->fromStorageLocation
            : $voucher->warehouse?->storageLocations()->first();

        $adjustmentType = $voucherItem->adjustment_type ?? 'positive';

        if ($adjustmentType === 'positive') {
            // Positive adjustment - add stock
            $batch = $this->getOrCreateBatch($voucherItem, $storageLocation);
            $runningBalance = $this->getRunningBalance($voucherItem->item_id, $storageLocation?->id) + $quantityBase;

            StockMovement::create([
                'voucher_id' => $voucher->id,
                'voucher_item_id' => $voucherItem->id,
                'item_id' => $voucherItem->item_id,
                'batch_id' => $batch->id,
                'movement_type' => 'in',
                'direction' => 'in',
                'warehouse_id' => $voucher->warehouse_id,
                'storage_location_id' => $storageLocation?->id,
                'kitchen_id' => null,
                'unit_id' => $voucherItem->unit_id,
                'quantity' => $voucherItem->quantity,
                'conversion_factor' => $voucherItem->conversion_factor,
                'quantity_base' => $quantityBase,
                'unit_price' => $unitPrice,
                'total_cost' => $totalCost,
                'running_balance_base' => $runningBalance,
                'performed_by' => $performedBy,
                'movement_date' => $voucher->transaction_date,
                'notes' => 'Positive adjustment: '.($voucherItem->notes ?? ''),
            ]);

            $batch->addQuantity($quantityBase);
        } else {
            // Negative adjustment - remove stock (waste)
            $batches = $this->selectBatchesFefo($voucherItem->item_id, $quantityBase, $storageLocation?->id, $voucher->warehouse_id);

            $remainingQty = $quantityBase;
            foreach ($batches as $batch) {
                $deductQty = min($remainingQty, $batch->quantity_remaining);

                $runningBalance = $this->getRunningBalance($voucherItem->item_id, $storageLocation?->id) - $deductQty;

                StockMovement::create([
                    'voucher_id' => $voucher->id,
                    'voucher_item_id' => $voucherItem->id,
                    'item_id' => $voucherItem->item_id,
                    'batch_id' => $batch->id,
                    'movement_type' => 'out',
                    'direction' => 'out',
                    'warehouse_id' => $voucher->warehouse_id,
                    'storage_location_id' => $storageLocation?->id,
                    'kitchen_id' => null,
                    'unit_id' => $voucherItem->unit_id,
                    'quantity' => $voucherItem->unit->fromBaseUnit($deductQty),
                    'conversion_factor' => $voucherItem->conversion_factor,
                    'quantity_base' => $deductQty,
                    'unit_price' => $batch->unit_cost ?: $unitPrice,
                    'total_cost' => $deductQty * ($batch->unit_cost ?: $unitPrice),
                    'running_balance_base' => $runningBalance,
                    'performed_by' => $performedBy,
                    'movement_date' => $voucher->transaction_date,
                    'notes' => 'Negative adjustment/Waste: '.($voucherItem->notes ?? ''),
                ]);

                $batch->deductQuantity($deductQty);
                $remainingQty -= $deductQty;

                if ($remainingQty <= 0) {
                    break;
                }
            }

            if ($remainingQty > 0) {
                throw new \Exception("Insufficient stock for waste adjustment. Short by {$remainingQty} base units.");
            }
        }
    }

    /**
     * Get or create a batch for the item at a storage location.
     */
    protected function getOrCreateBatch(StockVoucherItem $voucherItem, $storageLocation, $sourceBatch = null): ItemBatch
    {
        $item = $voucherItem->item;

        // If voucher item has a batch, use it
        if ($voucherItem->batch_id) {
            return $voucherItem->batch;
        }

        // If source batch provided (for transfers), create new batch with same info
        if ($sourceBatch) {
            return ItemBatch::create([
                'item_id' => $item->id,
                'batch_number' => $sourceBatch->batch_number.'-TRF-'.Str::random(4),
                'production_date' => $sourceBatch->production_date,
                'expiry_date' => $sourceBatch->expiry_date,
                'storage_location_id' => $storageLocation?->id,
                'quantity_received' => 0,
                'quantity_remaining' => 0,
                'unit_cost' => $sourceBatch->unit_cost,
                'status' => 'active',
                'notes' => 'Created from transfer of batch '.$sourceBatch->batch_number,
            ]);
        }

        // Find existing active batch at this location for this item
        $existingBatch = ItemBatch::where('item_id', $item->id)
            ->where('storage_location_id', $storageLocation?->id)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->latest('expiry_date')
            ->first();

        if ($existingBatch) {
            return $existingBatch;
        }

        // Create new batch
        return ItemBatch::create([
            'item_id' => $item->id,
            'batch_number' => 'BATCH-'.now()->format('Ymd').'-'.Str::random(6),
            'production_date' => now()->toDate(),
            'expiry_date' => $item->track_expiry ? now()->addYear()->toDate() : null,
            'storage_location_id' => $storageLocation?->id,
            'quantity_received' => 0,
            'quantity_remaining' => 0,
            'unit_cost' => $voucherItem->unit_price,
            'status' => 'active',
        ]);
    }

    /**
     * Select batches using FEFO (First Expired First Out) logic.
     * Falls back to warehouse-wide then global search if specific location has insufficient stock.
     */
    protected function selectBatchesFefo(int $itemId, float $quantityBase, ?int $storageLocationId = null, ?int $warehouseId = null): Collection
    {
        // First try: specific location
        $query = ItemBatch::where('item_id', $itemId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date', 'asc');

        if ($storageLocationId) {
            $query->where('storage_location_id', $storageLocationId);
        }

        $batches = $query->get();
        $availableQty = $batches->sum('quantity_remaining');

        // If enough at specific location, return them
        if ($availableQty >= $quantityBase) {
            return $batches;
        }

        // Second try: warehouse-wide (if warehouse provided)
        if ($warehouseId) {
            $query2 = ItemBatch::where('item_id', $itemId)
                ->where('status', 'active')
                ->where('quantity_remaining', '>', 0)
                ->whereHas('storageLocation', fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->orderBy('expiry_date', 'asc');

            $batches2 = $query2->get();
            $availableQty2 = $batches2->sum('quantity_remaining');

            if ($availableQty2 >= $quantityBase) {
                return $batches2;
            }
        }

        // Third try: global (all locations)
        $query3 = ItemBatch::where('item_id', $itemId)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date', 'asc');

        return $query3->get();
    }

    /**
     * Get running balance for an item at a location.
     */
    protected function getRunningBalance(int $itemId, ?int $storageLocationId = null): float
    {
        $query = StockMovement::where('item_id', $itemId)
            ->whereIn('movement_type', ['in', 'out', 'transfer']);

        if ($storageLocationId) {
            $query->where('storage_location_id', $storageLocationId);
        }

        $lastMovement = $query->latest('movement_date')->first();

        return $lastMovement?->running_balance_base ?? 0;
    }

    /**
     * Get current stock for an item across all locations.
     */
    public function getCurrentStock(Item $item): float
    {
        return $item->stockMovements()
            ->whereIn('movement_type', ['in', 'out', 'transfer'])
            ->sum('quantity_base');
    }

    /**
     * Get current stock for an item at a specific location.
     */
    public function getCurrentStockAtLocation(Item $item, int $storageLocationId): float
    {
        return $item->stockMovements()
            ->where('storage_location_id', $storageLocationId)
            ->whereIn('movement_type', ['in', 'out', 'transfer'])
            ->sum('quantity_base');
    }

    /**
     * Generate next voucher number.
     */
    public function generateVoucherNumber(string $type): string
    {
        $prefix = match ($type) {
            'addition' => 'IN',
            'issue' => 'OUT',
            'return' => 'RET',
            'transfer' => 'TRF',
            'adjustment' => 'ADJ',
            default => 'VCH',
        };

        $date = now()->format('Ymd');
        $lastVoucher = StockVoucher::where('voucher_number', 'like', "{$prefix}-{$date}-%")
            ->latest('voucher_number')
            ->first();

        $sequence = 1;
        if ($lastVoucher) {
            $parts = explode('-', $lastVoucher->voucher_number);
            $sequence = (int) end($parts) + 1;
        }

        return "{$prefix}-{$date}-".str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
