<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockVoucherItem extends Model
{
    protected $fillable = [
        'voucher_id',
        'item_id',
        'batch_id',
        'unit_id',
        'quantity',
        'conversion_factor',
        'quantity_base',
        'unit_price',
        'total_cost',
        'from_storage_location_id',
        'to_storage_location_id',
        'notes',
        'adjustment_type',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'conversion_factor' => 'decimal:6',
        'quantity_base' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    /**
     * Get the voucher.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(StockVoucher::class);
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
     * Get the unit used in transaction.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the from storage location.
     */
    public function fromStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'from_storage_location_id');
    }

    /**
     * Get the to storage location.
     */
    public function toStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'to_storage_location_id');
    }

    /**
     * Get the user who created this line item.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the stock movements for this line item.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'voucher_item_id');
    }

    /**
     * Calculate quantity_base before saving.
     */
    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->quantity_base = $item->quantity * $item->conversion_factor;
            $item->total_cost = $item->quantity_base * $item->unit_price;
        });
    }
}
