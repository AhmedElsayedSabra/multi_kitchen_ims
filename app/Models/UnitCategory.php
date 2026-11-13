<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the units in this category.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class)->orderBy('sort_order');
    }

    /**
     * Get the base unit for this category.
     */
    public function baseUnit(): HasMany
    {
        return $this->hasMany(Unit::class)->where('is_base_unit', true);
    }

    /**
     * Get the user who created this category.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this category.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the items using this unit category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
