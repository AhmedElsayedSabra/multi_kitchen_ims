<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'description',
        'image',
        'sort_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'string',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the subcategories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
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
     * Get items in this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    /**
     * Get items in this subcategory.
     */
    public function itemsAsSubcategory(): HasMany
    {
        return $this->hasMany(Item::class, 'subcategory_id');
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for root categories (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get full category path.
     */
    public function getFullPathAttribute(): string
    {
        $path = $this->name;
        $parent = $this->parent;
        while ($parent) {
            $path = $parent->name.' > '.$path;
            $parent = $parent->parent;
        }

        return $path;
    }
}
