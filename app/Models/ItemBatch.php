<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id',
        'batch_number',
        'production_date',
        'expiry_date',
        'storage_location_id',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'expiry_date' => 'date',
        'quantity_received' => 'decimal:4',
        'quantity_remaining' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'status' => 'string',
    ];

    const STATUSES = [
        'active' => 'Active',
        'consumed' => 'Consumed',
        'expired' => 'Expired',
        'wasted' => 'Wasted',
        'returned' => 'Returned',
    ];

    /**
     * Get the item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the storage location.
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    /**
     * Get the user who created this batch.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this batch.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the stock voucher items for this batch.
     */
    public function stockVoucherItems(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class);
    }

    /**
     * Get the stock movements for this batch.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date < now()->toDate();
    }

    /**
     * Check if batch is expiring soon (within alert days).
     */
    public function isExpiringSoon(int $days = 7): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date <= now()->addDays($days)->toDate() && ! $this->isExpired();
    }

    /**
     * Get expiry status.
     */
    public function getExpiryStatusAttribute(): array
    {
        if ($this->isExpired()) {
            return ['status' => 'expired', 'label' => 'Expired', 'color' => 'red'];
        } elseif ($this->isExpiringSoon()) {
            return ['status' => 'expiring_soon', 'label' => 'Expiring Soon', 'color' => 'amber'];
        } else {
            return ['status' => 'valid', 'label' => 'Valid', 'color' => 'green'];
        }
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Scope for active batches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired batches.
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now()->toDate())
            ->where('status', '!=', 'expired');
    }

    /**
     * Scope for expiring soon.
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days)->toDate())
            ->where('expiry_date', '>=', now()->toDate())
            ->where('status', 'active');
    }

    /**
     * Scope for FEFO (First Expired First Out) ordering.
     */
    public function scopeFefo($query)
    {
        return $query->where('status', 'active')
            ->where('quantity_remaining', '>', 0)
            ->orderBy('expiry_date', 'asc');
    }

    /**
     * Deduct quantity from batch.
     */
    public function deductQuantity(float $quantityBase): bool
    {
        if ($this->quantity_remaining < $quantityBase) {
            return false;
        }

        $this->quantity_remaining -= $quantityBase;

        if ($this->quantity_remaining <= 0) {
            $this->quantity_remaining = 0;
            $this->status = 'consumed';
        }

        $this->save();

        return true;
    }

    /**
     * Add quantity to batch.
     */
    public function addQuantity(float $quantityBase): void
    {
        $this->quantity_remaining += $quantityBase;

        if ($this->status === 'consumed' && $this->quantity_remaining > 0) {
            $this->status = 'active';
        }

        $this->save();
    }
}
