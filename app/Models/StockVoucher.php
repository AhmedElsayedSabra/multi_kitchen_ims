<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockVoucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'voucher_number',
        'voucher_type',
        'transaction_date',
        'warehouse_id',
        'from_storage_location_id',
        'to_storage_location_id',
        'kitchen_id',
        'supplier_id',
        'reference_voucher_id',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'status' => 'string',
        'voucher_type' => 'string',
    ];

    const VOUCHER_TYPES = [
        'addition' => 'Stock Addition / Supply',
        'issue' => 'Stock Issue / Withdrawal',
        'return' => 'Stock Return',
        'transfer' => 'Internal Transfer',
        'adjustment' => 'Adjustment / Waste',
    ];

    const STATUSES = [
        'draft' => 'Draft',
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Get the warehouse.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
     * Get the kitchen.
     */
    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class);
    }

    /**
     * Get the supplier (user).
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * Get the reference voucher.
     */
    public function referenceVoucher(): BelongsTo
    {
        return $this->belongsTo(StockVoucher::class, 'reference_voucher_id');
    }

    /**
     * Get the user who created this voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this voucher.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who last updated this voucher.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the voucher items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockVoucherItem::class, 'voucher_id');
    }

    /**
     * Get the stock movements.
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if voucher can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    /**
     * Check if voucher can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get voucher type label.
     */
    public function getVoucherTypeLabelAttribute(): string
    {
        return self::VOUCHER_TYPES[$this->voucher_type] ?? $this->voucher_type;
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Get status color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'pending' => 'amber',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Scope for approved vouchers.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending vouchers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope by voucher type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('voucher_type', $type);
    }

    /**
     * Scope by date range.
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
