<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Kitchen;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Warehouse;

class ReportService
{
    protected $quantityFormatter;

    public function __construct(QuantityFormatterService $quantityFormatter)
    {
        $this->quantityFormatter = $quantityFormatter;
    }

    /**
     * 1. Comprehensive Current Stock Balance Report
     */
    public function getCurrentStockReport(array $filters = []): array
    {
        $query = Item::with([
            'category', 'subcategory', 'baseUnit', 'defaultStorageLocation.warehouse',
            'activeBatches.storageLocation',
        ])->active();

        // Apply filters
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (! empty($filters['subcategory_id'])) {
            $query->where('subcategory_id', $filters['subcategory_id']);
        }
        if (! empty($filters['storage_location_id'])) {
            $query->where('default_storage_location_id', $filters['storage_location_id']);
        }
        if (! empty($filters['item_search'])) {
            $search = $filters['item_search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['stock_status'])) {
            // This would need a more complex query or post-filtering
        }

        $items = $query->get();

        $reportData = [];
        $totalValuation = 0;

        foreach ($items as $item) {
            $currentStock = $item->current_stock;
            $valuation = $currentStock * $item->cost_price;
            $totalValuation += $valuation;

            $breakdown = $this->quantityFormatter->getBreakdown($item, $currentStock);

            $reportData[] = [
                'item' => $item,
                'current_stock' => $currentStock,
                'formatted_stock' => $breakdown['formatted'],
                'breakdown' => $breakdown['breakdown'],
                'min_level' => $item->min_level,
                'max_level' => $item->max_level,
                'reorder_point' => $item->reorder_point,
                'stock_status' => $item->stock_status,
                'cost_price' => $item->cost_price,
                'valuation' => $valuation,
                'formatted_valuation' => number_format($valuation, 2),
                'default_location' => $item->defaultStorageLocation?->name ?? 'N/A',
                'batches' => $item->activeBatches->map(function ($batch) {
                    return [
                        'batch_number' => $batch->batch_number,
                        'quantity' => $batch->quantity_remaining,
                        'formatted_qty' => $this->quantityFormatter->formatQuantity($batch->item, $batch->quantity_remaining),
                        'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                        'expiry_status' => $batch->expiry_status,
                        'location' => $batch->storageLocation?->name,
                    ];
                })->toArray(),
            ];
        }

        return [
            'data' => $reportData,
            'summary' => [
                'total_items' => count($reportData),
                'total_valuation' => $totalValuation,
                'formatted_total_valuation' => number_format($totalValuation, 2),
                'out_of_stock' => collect($reportData)->where('stock_status.status', 'out_of_stock')->count(),
                'below_min' => collect($reportData)->where('stock_status.status', 'below_min')->count(),
                'needs_reorder' => collect($reportData)->where('stock_status.status', 'needs_reorder')->count(),
                'optimal' => collect($reportData)->where('stock_status.status', 'optimal')->count(),
                'overstocked' => collect($reportData)->where('stock_status.status', 'overstocked')->count(),
            ],
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * 2. Kitchen Consumption Report
     */
    public function getKitchenConsumptionReport(array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? now()->subMonth()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->endOfMonth()->toDateString();

        $query = StockMovement::with(['item.category', 'item.baseUnit', 'kitchen', 'voucher'])
            ->where('movement_type', 'out')
            ->where('direction', 'out')
            ->whereBetween('movement_date', [$fromDate, $toDate]);

        if (! empty($filters['kitchen_id'])) {
            $query->where('kitchen_id', $filters['kitchen_id']);
        }
        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('item', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $movements = $query->get();

        // Group by kitchen
        $byKitchen = $movements->groupBy('kitchen_id')->map(function ($kitchenMovements, $kitchenId) {
            $kitchen = $kitchenMovements->first()->kitchen;

            $items = $kitchenMovements->groupBy('item_id')->map(function ($itemMovements) {
                $item = $itemMovements->first()->item;
                $totalQty = $itemMovements->sum('quantity_base');
                $totalCost = $itemMovements->sum('total_cost');

                return [
                    'item' => $item,
                    'total_quantity_base' => $totalQty,
                    'formatted_quantity' => $this->quantityFormatter->formatQuantity($item, $totalQty),
                    'total_cost' => $totalCost,
                    'formatted_cost' => number_format($totalCost, 2),
                    'movements_count' => $itemMovements->count(),
                ];
            })->values();

            $kitchenTotalQty = $kitchenMovements->sum('quantity_base');
            $kitchenTotalCost = $kitchenMovements->sum('total_cost');

            return [
                'kitchen' => $kitchen,
                'items' => $items,
                'total_quantity' => $kitchenTotalQty,
                'total_cost' => $kitchenTotalCost,
                'formatted_total_cost' => number_format($kitchenTotalCost, 2),
            ];
        })->values();

        // Also group by item
        $byItem = $movements->groupBy('item_id')->map(function ($itemMovements) {
            $item = $itemMovements->first()->item;
            $totalQty = $itemMovements->sum('quantity_base');
            $totalCost = $itemMovements->sum('total_cost');

            $byKitchen = $itemMovements->groupBy('kitchen_id')->map(function ($kMovements) {
                $kitchen = $kMovements->first()->kitchen;

                return [
                    'kitchen' => $kitchen,
                    'quantity' => $kMovements->sum('quantity_base'),
                    'cost' => $kMovements->sum('total_cost'),
                ];
            })->values();

            return [
                'item' => $item,
                'total_quantity' => $totalQty,
                'formatted_quantity' => $this->quantityFormatter->formatQuantity($item, $totalQty),
                'total_cost' => $totalCost,
                'by_kitchen' => $byKitchen,
            ];
        })->values();

        return [
            'by_kitchen' => $byKitchen,
            'by_item' => $byItem,
            'date_range' => ['from' => $fromDate, 'to' => $toDate],
            'summary' => [
                'total_consumed' => $movements->sum('quantity_base'),
                'total_cost' => $movements->sum('total_cost'),
                'total_vouchers' => $movements->pluck('voucher_id')->unique()->count(),
            ],
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * 3. Movement History / Ledger Report
     */
    public function getMovementLedgerReport(array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? now()->subMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        $query = StockMovement::with(['item', 'item.baseUnit', 'voucher', 'kitchen', 'storageLocation', 'performer', 'batch'])
            ->whereBetween('movement_date', [$fromDate, $toDate])
            ->orderBy('movement_date', 'desc');

        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (! empty($filters['voucher_type'])) {
            $query->whereHas('voucher', function ($q) use ($filters) {
                $q->where('voucher_type', $filters['voucher_type']);
            });
        }
        if (! empty($filters['kitchen_id'])) {
            $query->where('kitchen_id', $filters['kitchen_id']);
        }
        if (! empty($filters['storage_location_id'])) {
            $query->where('storage_location_id', $filters['storage_location_id']);
        }
        if (! empty($filters['user_id'])) {
            $query->where('performed_by', $filters['user_id']);
        }
        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        $movements = $query->paginate(50);

        $data = $movements->getCollection()->map(function ($movement) {
            return [
                'date' => $movement->movement_date->format('Y-m-d H:i:s'),
                'voucher_number' => $movement->voucher->voucher_number ?? 'N/A',
                'voucher_type' => $movement->voucher->voucher_type_label ?? 'N/A',
                'item' => $movement->item->name ?? 'N/A',
                'item_sku' => $movement->item->sku ?? 'N/A',
                'batch_number' => $movement->batch->batch_number ?? 'N/A',
                'movement_type' => $movement->movement_type_label,
                'direction' => $movement->direction,
                'quantity' => $movement->quantity,
                'unit' => $movement->unit->symbol ?? 'N/A',
                'quantity_base' => $movement->quantity_base,
                'formatted_qty' => $this->quantityFormatter->formatQuantity($movement->item, $movement->quantity_base),
                'unit_price' => $movement->unit_price,
                'total_cost' => $movement->total_cost,
                'running_balance' => $movement->running_balance_base,
                'formatted_balance' => $this->quantityFormatter->formatQuantity($movement->item, $movement->running_balance_base),
                'kitchen' => $movement->kitchen->name ?? 'N/A',
                'location' => $movement->storageLocation->name ?? 'N/A',
                'performed_by' => $movement->performer->name ?? 'N/A',
                'notes' => $movement->notes,
            ];
        });

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
            ],
            'date_range' => ['from' => $fromDate, 'to' => $toDate],
            'summary' => [
                'total_in' => StockMovement::whereBetween('movement_date', [$fromDate, $toDate])
                    ->where('direction', 'in')->sum('quantity_base'),
                'total_out' => StockMovement::whereBetween('movement_date', [$fromDate, $toDate])
                    ->where('direction', 'out')->sum('quantity_base'),
                'total_movements' => $movements->total(),
            ],
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * 4. Valuation & Cost Report per Kitchen and Warehouse
     */
    public function getValuationReport(array $filters = []): array
    {
        // Valuation by Warehouse/Location
        $locationValuation = StorageLocation::with('warehouse')
            ->active()
            ->get()
            ->map(function ($location) {
                $items = Item::active()->where('default_storage_location_id', $location->id)->get();

                $locationItems = $items->map(function ($item) use ($location) {
                    $stock = $this->getStockAtLocation($item, $location->id);

                    return [
                        'item' => $item,
                        'stock' => $stock,
                        'formatted_stock' => $this->quantityFormatter->formatQuantity($item, $stock),
                        'cost_price' => $item->cost_price,
                        'valuation' => $stock * $item->cost_price,
                    ];
                })->filter(fn ($i) => $i['stock'] > 0);

                $totalValuation = $locationItems->sum('valuation');

                return [
                    'location' => $location,
                    'warehouse' => $location->warehouse,
                    'items' => $locationItems->values(),
                    'total_valuation' => $totalValuation,
                    'formatted_total' => number_format($totalValuation, 2),
                    'item_count' => $locationItems->count(),
                ];
            })->filter(fn ($l) => $l['item_count'] > 0);

        // Valuation by Kitchen (consumption value)
        $kitchenValuation = Kitchen::active()->get()->map(function ($kitchen) {
            $fromDate = $filters['from_date'] ?? now()->subMonth()->startOfMonth()->toDateString();
            $toDate = $filters['to_date'] ?? now()->endOfMonth()->toDateString();

            $movements = StockMovement::where('kitchen_id', $kitchen->id)
                ->where('direction', 'out')
                ->whereBetween('movement_date', [$fromDate, $toDate])
                ->with('item')
                ->get();

            $items = $movements->groupBy('item_id')->map(function ($itemMovements) {
                $item = $itemMovements->first()->item;

                return [
                    'item' => $item,
                    'quantity' => $itemMovements->sum('quantity_base'),
                    'cost' => $itemMovements->sum('total_cost'),
                ];
            });

            $totalCost = $items->sum('cost');

            return [
                'kitchen' => $kitchen,
                'items' => $items->values(),
                'total_consumption_value' => $totalCost,
                'formatted_total' => number_format($totalCost, 2),
            ];
        })->filter(fn ($k) => $k['total_consumption_value'] > 0);

        $grandTotal = $locationValuation->sum('total_valuation');

        return [
            'by_location' => $locationValuation->values(),
            'by_kitchen' => $kitchenValuation->values(),
            'grand_total_valuation' => $grandTotal,
            'formatted_grand_total' => number_format($grandTotal, 2),
            'date_range' => [
                'from' => $filters['from_date'] ?? now()->subMonth()->startOfMonth()->toDateString(),
                'to' => $filters['to_date'] ?? now()->endOfMonth()->toDateString(),
            ],
            'generated_at' => now(),
        ];
    }

    /**
     * 5. Stock Control & Reorder Report
     */
    public function getStockControlReport(array $filters = []): array
    {
        $query = Item::with(['category', 'baseUnit', 'defaultStorageLocation'])
            ->active();

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $items = $query->get();

        $reportData = $items->map(function ($item) {
            $currentStock = $item->current_stock;
            $suggestion = $this->quantityFormatter->calculateReorderSuggestion($item);

            return [
                'item' => $item,
                'current_stock' => $currentStock,
                'formatted_stock' => $this->quantityFormatter->formatQuantity($item, $currentStock),
                'min_level' => $item->min_level,
                'max_level' => $item->max_level,
                'reorder_point' => $item->reorder_point,
                'stock_status' => $item->stock_status,
                'needs_reorder' => $suggestion['needs_reorder'],
                'suggested_order_qty' => $suggestion['suggested_order_qty'],
                'suggested_order_formatted' => $suggestion['suggested_order_formatted'],
                'default_location' => $item->defaultStorageLocation?->name ?? 'N/A',
            ];
        });

        // Filter by status if specified
        if (! empty($filters['stock_status'])) {
            $reportData = $reportData->filter(fn ($item) => $item['stock_status']['status'] === $filters['stock_status']);
        }

        // Sort: Out of stock first, then below min, then needs reorder
        $statusOrder = ['out_of_stock' => 0, 'below_min' => 1, 'needs_reorder' => 2, 'optimal' => 3, 'overstocked' => 4];
        $reportData = $reportData->sortBy(fn ($item) => $statusOrder[$item['stock_status']['status']] ?? 5);

        return [
            'data' => $reportData->values(),
            'summary' => [
                'total_items' => $items->count(),
                'out_of_stock' => $reportData->where('stock_status.status', 'out_of_stock')->count(),
                'below_min' => $reportData->where('stock_status.status', 'below_min')->count(),
                'needs_reorder' => $reportData->where('stock_status.status', 'needs_reorder')->count(),
                'optimal' => $reportData->where('stock_status.status', 'optimal')->count(),
                'overstocked' => $reportData->where('stock_status.status', 'overstocked')->count(),
            ],
            'reorder_list' => $reportData->where('needs_reorder', true)
                ->map(fn ($item) => [
                    'item' => $item['item'],
                    'current_stock' => $item['formatted_stock'],
                    'reorder_point' => $item['reorder_point'],
                    'suggested_qty' => $item['suggested_order_formatted'],
                    'location' => $item['default_location'],
                ])->values(),
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * 6. Expiry & Physical Location Tracking Report
     */
    public function getExpiryTrackingReport(array $filters = []): array
    {
        $alertDays = $filters['alert_days'] ?? config('system.expiry_alert_days', 7);

        $query = ItemBatch::with(['item', 'item.category', 'item.baseUnit', 'storageLocation.warehouse'])
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0);

        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (! empty($filters['storage_location_id'])) {
            $query->where('storage_location_id', $filters['storage_location_id']);
        }
        if (! empty($filters['warehouse_id'])) {
            $query->whereHas('storageLocation', function ($q) use ($filters) {
                $q->where('warehouse_id', $filters['warehouse_id']);
            });
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('item', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        $batches = $query->get();

        // Categorize batches
        $expired = $batches->filter(fn ($b) => $b->isExpired());
        $expiringSoon = $batches->filter(fn ($b) => $b->isExpiringSoon($alertDays) && ! $b->isExpired());
        $valid = $batches->filter(fn ($b) => ! $b->isExpired() && ! $b->isExpiringSoon($alertDays));

        // By location
        $byLocation = $batches->groupBy('storage_location_id')->map(function ($locationBatches, $locationId) {
            $location = $locationBatches->first()->storageLocation;

            return [
                'location' => $location,
                'warehouse' => $location?->warehouse,
                'expired' => $locationBatches->filter(fn ($b) => $b->isExpired())->values(),
                'expiring_soon' => $locationBatches->filter(fn ($b) => $b->isExpiringSoon(7) && ! $b->isExpired())->values(),
                'valid' => $locationBatches->filter(fn ($b) => ! $b->isExpired() && ! $b->isExpiringSoon(7))->values(),
                'total_batches' => $locationBatches->count(),
                'total_quantity' => $locationBatches->sum('quantity_remaining'),
            ];
        });

        return [
            'expired' => $expired->map(function ($batch) {
                return [
                    'batch' => $batch,
                    'item' => $batch->item,
                    'formatted_qty' => $this->quantityFormatter->formatQuantity($batch->item, $batch->quantity_remaining),
                    'days_expired' => abs($batch->days_until_expiry),
                    'location' => $batch->storageLocation,
                ];
            })->values(),
            'expiring_soon' => $expiringSoon->map(function ($batch) {
                return [
                    'batch' => $batch,
                    'item' => $batch->item,
                    'formatted_qty' => $this->quantityFormatter->formatQuantity($batch->item, $batch->quantity_remaining),
                    'days_until_expiry' => $batch->days_until_expiry,
                    'location' => $batch->storageLocation,
                ];
            })->sortBy('days_until_expiry')->values(),
            'valid' => $valid->map(function ($batch) {
                return [
                    'batch' => $batch,
                    'item' => $batch->item,
                    'formatted_qty' => $this->quantityFormatter->formatQuantity($batch->item, $batch->quantity_remaining),
                    'days_until_expiry' => $batch->days_until_expiry,
                    'location' => $batch->storageLocation,
                ];
            })->values(),
            'by_location' => $byLocation->values(),
            'summary' => [
                'total_batches' => $batches->count(),
                'expired_count' => $expired->count(),
                'expiring_soon_count' => $expiringSoon->count(),
                'valid_count' => $valid->count(),
                'expired_quantity' => $expired->sum('quantity_remaining'),
                'expiring_soon_quantity' => $expiringSoon->sum('quantity_remaining'),
            ],
            'alert_days' => $alertDays,
            'filters' => $filters,
            'generated_at' => now(),
        ];
    }

    /**
     * Get stock for an item at a specific location.
     */
    protected function getStockAtLocation(Item $item, int $storageLocationId): float
    {
        $in = $item->stockMovements()
            ->where('storage_location_id', $storageLocationId)
            ->where('direction', 'in')
            ->whereIn('movement_type', ['in', 'out', 'transfer'])
            ->sum('quantity_base');

        $out = $item->stockMovements()
            ->where('storage_location_id', $storageLocationId)
            ->where('direction', 'out')
            ->whereIn('movement_type', ['in', 'out', 'transfer'])
            ->sum('quantity_base');

        return $in - $out;
    }
}
