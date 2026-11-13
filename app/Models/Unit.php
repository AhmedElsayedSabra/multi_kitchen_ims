<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_category_id',
        'parent_unit_id',
        'name',
        'symbol',
        'is_base_unit',
        'conversion_factor',
        'sort_order',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'conversion_factor' => 'decimal:6',
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the unit category this unit belongs to.
     */
    public function unitCategory(): BelongsTo
    {
        return $this->belongsTo(UnitCategory::class);
    }

    /**
     * Get the parent unit (for nested packaging).
     */
    public function parentUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_unit_id');
    }

    /**
     * Get the child units (sub-units).
     */
    public function childUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_unit_id')->orderBy('sort_order');
    }

    /**
     * Get the user who created this unit.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this unit.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get items using this as base unit.
     */
    public function itemsAsBaseUnit(): HasMany
    {
        return $this->hasMany(Item::class, 'base_unit_id');
    }

    /**
     * Get items using this as default purchase unit.
     */
    public function itemsAsPurchaseUnit(): HasMany
    {
        return $this->hasMany(Item::class, 'default_purchase_unit_id');
    }

    /**
     * Get items using this as default issue unit.
     */
    public function itemsAsIssueUnit(): HasMany
    {
        return $this->hasMany(Item::class, 'default_issue_unit_id');
    }

    /**
     * Get stock voucher items using this unit.
     */
    public function stockVoucherItems(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class);
    }

    /**
     * Get stock movements using this unit.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Convert quantity from this unit to base unit.
     */
    public function toBaseUnit(float $quantity): float
    {
        return $quantity * $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit to this unit.
     */
    public function fromBaseUnit(float $quantity): float
    {
        if ($this->conversion_factor == 0) {
            return 0;
        }

        return $quantity / $this->conversion_factor;
    }

    /**
     * Convert quantity from this unit to another unit in the same category.
     */
    public function convertTo(Unit $targetUnit, float $quantity): float
    {
        if ($this->unit_category_id !== $targetUnit->unit_category_id) {
            throw new \InvalidArgumentException('Cannot convert between different unit categories');
        }
        $baseQuantity = $this->toBaseUnit($quantity);

        return $targetUnit->fromBaseUnit($baseQuantity);
    }

    /**
     * Scope for active units.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for base units.
     */
    public function scopeBaseUnits($query)
    {
        return $query->where('is_base_unit', true);
    }

    /**
     * Get display name with symbol.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->symbol})";
    }
}
