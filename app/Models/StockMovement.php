<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'voucher_id',
        'voucher_item_id',
        'item_id',
        'batch_id',
        'movement_type',
        'direction',
        'warehouse_id',
        'storage_location_id',
        'kitchen_id',
        'unit_id',
        'quantity',
        'conversion_factor',
        'quantity_base',
        'unit_price',
        'total_cost',
        'running_balance_base',
        'performed_by',
        'movement_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'conversion_factor' => 'decimal:6',
        'quantity_base' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'running_balance_base' => 'decimal:4',
        'movement_date' => 'datetime',
        'movement_type' => 'string',
        'direction' => 'string',
    ];

    const MOVEMENT_TYPES = [
        'in' => 'Stock In',
        'out' => 'Stock Out',
        'transfer' => 'Transfer',
    ];

    /**
     * Get the voucher.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(StockVoucher::class);
    }

    /**
     * Get the voucher item.
     */
    public function voucherItem(): BelongsTo
    {
        return $this->belongsTo(StockVoucherItem::class, 'voucher_item_id');
    }

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the batch.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the storage location.
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Get the kitchen.
     */
    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class);
    }

    /**
     * Get the unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user who performed this movement.
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get movement type label.
     */
    public function getMovementTypeLabelAttribute(): string
    {
        return self::MOVEMENT_TYPES[$this->movement_type] ?? $this->movement_type;
    }

    /**
     * Scope by movement type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    /**
     * Scope by direction.
     */
    public function scopeDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    /**
     * Scope by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }
}
