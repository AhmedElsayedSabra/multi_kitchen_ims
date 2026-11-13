<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'type',
        'description',
        'capacity',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the warehouse this location belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this location.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this location.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the items with this as default storage location.
     */
    public function defaultItems(): HasMany
    {
        return $this->hasMany(Item::class, 'default_storage_location_id');
    }

    /**
     * Get the stock voucher items from this location.
     */
    public function fromVoucherItems(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class, 'from_storage_location_id');
    }

    /**
     * Get the stock voucher items to this location.
     */
    public function toVoucherItems(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class, 'to_storage_location_id');
    }

    /**
     * Get the stock movements for this location.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the item batches stored in this location.
     */
    public function itemBatches(): HasMany
    {
        return $this->hasMany(ItemBatch::class);
    }

    /**
     * Scope for active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
