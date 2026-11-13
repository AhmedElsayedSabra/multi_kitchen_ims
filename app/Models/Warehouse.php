<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'manager_name',
        'phone',
        'email',
        'address',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the user who created this warehouse.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this warehouse.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the storage locations for this warehouse.
     */
    public function storageLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }

    /**
     * Get the stock vouchers for this warehouse.
     */
    public function stockVouchers(): HasMany
    {
        return $this->hasMany(StockVoucher::class);
    }

    /**
     * Get the stock movements for this warehouse.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the users assigned to this warehouse.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope for active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
