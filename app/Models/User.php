<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'avatar', 'status', 'kitchen_id', 'warehouse_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
        ];
    }

    /**
     * Get the kitchen this user belongs to.
     */
    public function kitchen(): BelongsTo
    {
        return $this->belongsTo(Kitchen::class);
    }

    /**
     * Get the warehouse this user belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the kitchens this user created.
     */
    public function createdKitchens(): HasMany
    {
        return $this->hasMany(Kitchen::class, 'created_by');
    }

    /**
     * Get the warehouses this user created.
     */
    public function createdWarehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'created_by');
    }

    /**
     * Get the items this user created.
     */
    public function createdItems(): HasMany
    {
        return $this->hasMany(Item::class, 'created_by');
    }

    /**
     * Get the stock vouchers this user created.
     */
    public function createdVouchers(): HasMany
    {
        return $this->hasMany(StockVoucher::class, 'created_by');
    }

    /**
     * Get the stock vouchers this user approved.
     */
    public function approvedVouchers(): HasMany
    {
        return $this->hasMany(StockVoucher::class, 'approved_by');
    }

    /**
     * Get the stock movements this user performed.
     */
    public function performedMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'performed_by');
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is warehouse manager.
     */
    public function isWarehouseManager(): bool
    {
        return $this->hasRole('warehouse_manager');
    }

    /**
     * Check if user is viewer.
     */
    public function isViewer(): bool
    {
        return $this->hasRole('viewer');
    }

    /**
     * Check if user can manage system settings.
     */
    public function canManageSettings(): bool
    {
        return $this->hasPermissionTo('manage settings');
    }

    /**
     * Check if user can manage users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasPermissionTo('manage users');
    }

    /**
     * Get user's display name with role.
     */
    public function getDisplayNameAttribute(): string
    {
        $roles = $this->getRoleNames()->implode(', ');

        return "{$this->name} ({$roles})";
    }
}
