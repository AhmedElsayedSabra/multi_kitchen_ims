<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'category_id',
        'subcategory_id',
        'unit_category_id',
        'base_unit_id',
        'default_purchase_unit_id',
        'default_issue_unit_id',
        'purchase_price',
        'cost_price',
        'min_level',
        'max_level',
        'reorder_point',
        'default_storage_location_id',
        'track_expiry',
        'is_active',
        'description',
        'image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'min_level' => 'decimal:4',
        'max_level' => 'decimal:4',
        'reorder_point' => 'decimal:4',
        'track_expiry' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the subcategory.
     */
    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /**
     * Get the unit category.
     */
    public function unitCategory(): BelongsTo
    {
        return $this->belongsTo(UnitCategory::class);
    }

    /**
     * Get the base unit.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get the default purchase unit.
     */
    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_purchase_unit_id');
    }

    /**
     * Get the default issue unit.
     */
    public function issueUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'default_issue_unit_id');
    }

    /**
     * Get the default storage location.
     */
    public function defaultStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'default_storage_location_id');
    }

    /**
     * Get the user who created this item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this item.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the batches for this item.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ItemBatch::class)->orderBy('expiry_date');
    }

    /**
     * Get active batches (with remaining quantity).
     */
    public function activeBatches(): HasMany
    {
        return $this->hasMany(ItemBatch::class)
            ->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date');
    }

    /**
     * Get stock voucher items for this item.
     */
    public function stockVoucherItems(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class);
    }

    /**
     * Get stock movements for this item.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get current stock balance in base unit.
     */
    public function getCurrentStockAttribute(): float
    {
        return $this->stockMovements()
            ->where('direction', 'in')
            ->sum('quantity_base')
            - $this->stockMovements()
                ->where('direction', 'out')
                ->sum('quantity_base');
    }

    /**
     * Get current stock balance for a specific kitchen/warehouse.
     */
    public function getCurrentStockForLocation(?int $storageLocationId = null, ?int $kitchenId = null): float
    {
        $query = $this->stockMovements();

        if ($storageLocationId) {
            $query->where('storage_location_id', $storageLocationId);
        }

        if ($kitchenId) {
            $query->where('kitchen_id', $kitchenId);
        }

        return $query->where('direction', 'in')->sum('quantity_base')
            - $query->where('direction', 'out')->sum('quantity_base');
    }

    /**
     * Get stock status badge.
     */
    public function getStockStatusAttribute(): array
    {
        $stock = $this->current_stock;

        if ($stock <= 0) {
            return ['status' => 'out_of_stock', 'label' => 'Out of Stock', 'color' => 'red'];
        } elseif ($stock <= $this->min_level) {
            return ['status' => 'below_min', 'label' => 'Below Min Level', 'color' => 'orange'];
        } elseif ($stock <= $this->reorder_point) {
            return ['status' => 'needs_reorder', 'label' => 'Needs Reorder', 'color' => 'amber'];
        } elseif ($stock >= $this->max_level && $this->max_level > 0) {
            return ['status' => 'overstocked', 'label' => 'Overstocked', 'color' => 'purple'];
        } else {
            return ['status' => 'optimal', 'label' => 'Optimal Stock', 'color' => 'green'];
        }
    }

    /**
     * Get reorder quantity suggestion.
     */
    public function getReorderQuantityAttribute(): float
    {
        if ($this->max_level > 0) {
            return max(0, $this->max_level - $this->current_stock);
        }

        return 0;
    }

    /**
     * Scope for active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for items needing reorder.
     */
    public function scopeNeedsReorder($query)
    {
        return $query->where('is_active', true)
            ->whereRaw('(SELECT COALESCE(SUM(quantity_base), 0) FROM stock_movements WHERE item_id = items.id) <= reorder_point');
    }

    /**
     * Generate barcode if not set.
     */
    public function generateBarcode(): string
    {
        if (! $this->barcode) {
            $this->barcode = 'ITEM-'.str_pad($this->id ?? rand(100000, 999999), 8, '0', STR_PAD_LEFT);
            $this->save();
        }

        return $this->barcode;
    }
}
