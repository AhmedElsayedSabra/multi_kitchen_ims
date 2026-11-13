<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Unit;

class QuantityFormatterService
{
    /**
     * Format a base quantity into a human-readable composite breakdown.
     *
     * Example: 65 Pieces (base unit) with Carton = 12 Pieces, Packet = 12 Pieces
     * Returns: "65 Pieces (5 Cartons, 5 Pieces)" or "65 Pieces (5 Cartons, 0 Packets, 5 Pieces)"
     */
    public function formatQuantity(Item $item, float $quantityBase, bool $includeBaseUnit = true): string
    {
        $baseUnit = $item->baseUnit;
        if (! $baseUnit) {
            return $this->formatNumber($quantityBase).' units';
        }

        // Get all units in the same category, ordered by conversion factor descending
        $units = Unit::where('unit_category_id', $item->unit_category_id)
            ->where('is_base_unit', false)
            ->where('status', 'active')
            ->orderBy('conversion_factor', 'desc')
            ->get();

        if ($units->isEmpty()) {
            return $this->formatNumber($quantityBase).' '.$baseUnit->symbol;
        }

        $remaining = $quantityBase;
        $parts = [];

        foreach ($units as $unit) {
            if ($remaining <= 0) {
                break;
            }

            $unitQuantity = floor($remaining / $unit->conversion_factor);
            if ($unitQuantity > 0) {
                $parts[] = $this->formatNumber($unitQuantity).' '.$unit->symbol;
                $remaining -= $unitQuantity * $unit->conversion_factor;
            }
        }

        // Add remaining base units if any
        if ($remaining > 0 || empty($parts)) {
            $parts[] = $this->formatNumber($remaining).' '.$baseUnit->symbol;
        }

        $composite = implode(', ', $parts);

        if ($includeBaseUnit) {
            return $this->formatNumber($quantityBase).' '.$baseUnit->symbol.' ('.$composite.')';
        }

        return $composite;
    }

    /**
     * Get detailed breakdown as array for programmatic use.
     */
    public function getBreakdown(Item $item, float $quantityBase): array
    {
        $baseUnit = $item->baseUnit;
        if (! $baseUnit) {
            return [
                'base_quantity' => $quantityBase,
                'base_unit' => 'units',
                'breakdown' => [],
            ];
        }

        $units = Unit::where('unit_category_id', $item->unit_category_id)
            ->where('is_base_unit', false)
            ->where('status', 'active')
            ->orderBy('conversion_factor', 'desc')
            ->get();

        $remaining = $quantityBase;
        $breakdown = [];

        foreach ($units as $unit) {
            if ($remaining <= 0) {
                $breakdown[] = [
                    'unit_id' => $unit->id,
                    'unit_name' => $unit->name,
                    'unit_symbol' => $unit->symbol,
                    'quantity' => 0,
                    'conversion_factor' => $unit->conversion_factor,
                ];

                continue;
            }

            $unitQuantity = floor($remaining / $unit->conversion_factor);
            $breakdown[] = [
                'unit_id' => $unit->id,
                'unit_name' => $unit->name,
                'unit_symbol' => $unit->symbol,
                'quantity' => $unitQuantity,
                'conversion_factor' => $unit->conversion_factor,
            ];

            $remaining -= $unitQuantity * $unit->conversion_factor;
        }

        // Add base unit remainder
        $breakdown[] = [
            'unit_id' => $baseUnit->id,
            'unit_name' => $baseUnit->name,
            'unit_symbol' => $baseUnit->symbol,
            'quantity' => $remaining,
            'conversion_factor' => 1,
            'is_base' => true,
        ];

        return [
            'base_quantity' => $quantityBase,
            'base_unit' => [
                'id' => $baseUnit->id,
                'name' => $baseUnit->name,
                'symbol' => $baseUnit->symbol,
            ],
            'breakdown' => $breakdown,
            'formatted' => $this->formatQuantity($item, $quantityBase),
        ];
    }

    /**
     * Format quantity for a specific unit (not composite).
     */
    public function formatForUnit(float $quantity, Unit $unit, int $decimals = 2): string
    {
        return $this->formatNumber($quantity, $decimals).' '.$unit->symbol;
    }

    /**
     * Convert and format quantity from one unit to another.
     */
    public function convertAndFormat(float $quantity, Unit $fromUnit, Unit $toUnit, int $decimals = 4): string
    {
        if ($fromUnit->unit_category_id !== $toUnit->unit_category_id) {
            throw new \InvalidArgumentException('Units must be in the same category');
        }

        $baseQuantity = $fromUnit->toBaseUnit($quantity);
        $targetQuantity = $toUnit->fromBaseUnit($baseQuantity);

        return $this->formatNumber($targetQuantity, $decimals).' '.$toUnit->symbol;
    }

    /**
     * Format number with locale-aware formatting.
     */
    protected function formatNumber(float $number, int $decimals = 2): string
    {
        // Remove trailing zeros after decimal point
        $formatted = number_format($number, $decimals, '.', ',');

        // Trim trailing zeros
        if (str_contains($formatted, '.')) {
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, '.');
        }

        return $formatted;
    }

    /**
     * Get all possible unit conversions for an item (for dropdowns).
     */
    public function getUnitOptions(Item $item): array
    {
        return Unit::where('unit_category_id', $item->unit_category_id)
            ->where('status', 'active')
            ->orderBy('is_base_unit', 'desc')
            ->orderBy('conversion_factor', 'desc')
            ->get()
            ->mapWithKeys(function ($unit) {
                return [$unit->id => $unit->display_name];
            })
            ->toArray();
    }

    /**
     * Calculate reorder suggestion for an item.
     */
    public function calculateReorderSuggestion(Item $item): array
    {
        $currentStock = $item->current_stock;
        $reorderQty = max(0, $item->max_level - $currentStock);

        return [
            'current_stock' => $currentStock,
            'current_stock_formatted' => $this->formatQuantity($item, $currentStock),
            'min_level' => $item->min_level,
            'max_level' => $item->max_level,
            'reorder_point' => $item->reorder_point,
            'suggested_order_qty' => $reorderQty,
            'suggested_order_formatted' => $this->formatQuantity($item, $reorderQty),
            'status' => $item->stock_status,
            'needs_reorder' => $currentStock <= $item->reorder_point,
        ];
    }
}
