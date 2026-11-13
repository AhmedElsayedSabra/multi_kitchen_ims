<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Kitchen;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get key statistics
        $totalItems = Item::count();
        $totalWarehouses = Warehouse::count();
        $totalKitchens = Kitchen::count();

        // Low stock items - using current_stock accessor which sums stockMovements with direction
        $lowStockItems = Item::whereRaw('(
                SELECT COALESCE(SUM(CASE WHEN direction = \'in\' THEN quantity_base ELSE -quantity_base END), 0)
                FROM stock_movements
                WHERE item_id = items.id
            ) <= items.min_level')
            ->where('is_active', true)
            ->with(['baseUnit', 'defaultStorageLocation'])
            ->take(10)
            ->get();

        // Expiring soon batches
        $expiringSoon = ItemBatch::active()
            ->expiringSoon(30)
            ->with(['item', 'storageLocation'])
            ->take(10)
            ->get();

        // Recent movements
        $recentMovements = StockMovement::with(['item', 'storageLocation', 'voucher'])
            ->latest('movement_date')
            ->take(10)
            ->get();

        // Stock value by warehouse
        $warehouseStockValue = Warehouse::withSum('stockMovements as total_value', 'total_cost')
            ->get();

        // Monthly movements chart data (last 6 months)
        $monthlyMovements = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $in = StockMovement::where('direction', 'in')
                ->whereYear('movement_date', $month->year)
                ->whereMonth('movement_date', $month->month)
                ->sum('total_cost');
            $out = StockMovement::where('direction', 'out')
                ->whereYear('movement_date', $month->year)
                ->whereMonth('movement_date', $month->month)
                ->sum('total_cost');
            $monthlyMovements[] = [
                'month' => $month->format('M Y'),
                'in' => $in,
                'out' => $out,
            ];
        }

        return view('dashboard', compact(
            'totalItems',
            'totalWarehouses',
            'totalKitchens',
            'lowStockItems',
            'expiringSoon',
            'recentMovements',
            'warehouseStockValue',
            'monthlyMovements'
        ));
    }
}
