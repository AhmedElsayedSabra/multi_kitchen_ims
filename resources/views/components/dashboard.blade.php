<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\StockVoucher;
use App\Models\ItemBatch;
use App\Models\Kitchen;
use App\Models\Warehouse;
use App\Services\ReportService;
use App\Services\QuantityFormatterService;
use Carbon\Carbon;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public $stats = [];
    public $recentMovements = [];
    public $lowStockItems = [];
    public $expiringSoonItems = [];
    public $kitchenConsumption = [];

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        // Key Stats
        $this->stats = [
            'total_items' => Item::active()->count(),
            'total_kitchens' => Kitchen::active()->count(),
            'total_warehouses' => Warehouse::active()->count(),
            'pending_vouchers' => StockVoucher::where('status', 'pending')->count(),
            'total_stock_value' => Item::active()->get()->sum(fn($item) => $item->current_stock * $item->cost_price),
            'out_of_stock' => Item::active()->get()->filter(fn($item) => $item->current_stock <= 0)->count(),
            'below_min' => Item::active()->get()->filter(fn($item) => $item->current_stock > 0 && $item->current_stock <= $item->min_level)->count(),
            'expiring_soon' => ItemBatch::active()->expiringSoon(7)->count(),
        ];

        // Recent Movements
        $this->recentMovements = StockMovement::with(['item', 'voucher', 'kitchen', 'storageLocation'])
            ->latest('movement_date')
            ->take(10)
            ->get()
            ->map(function ($movement) {
                return [
                    'date' => $movement->movement_date->format('M d, Y H:i'),
                    'voucher' => $movement->voucher->voucher_number ?? 'N/A',
                    'type' => $movement->movement_type_label,
                    'item' => $movement->item->name ?? 'N/A',
                    'qty' => $movement->quantity . ' ' . ($movement->unit->symbol ?? ''),
                    'kitchen' => $movement->kitchen->name ?? '—',
                    'location' => $movement->storageLocation->name ?? '—',
                ];
            })->toArray();

        // Low Stock Items
        $this->lowStockItems = Item::active()->get()
            ->filter(fn($item) => $item->current_stock <= $item->reorder_point && $item->reorder_point > 0)
            ->sortBy('current_stock')
            ->take(10)
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'current' => $item->current_stock,
                    'min' => $item->min_level,
                    'reorder' => $item->reorder_point,
                    'unit' => $item->baseUnit->symbol ?? '',
                    'status' => $item->stock_status,
                ];
            })->values()->toArray();

        // Expiring Soon
        $this->expiringSoonItems = ItemBatch::with(['item', 'storageLocation'])
            ->active()
            ->expiringSoon(7)
            ->orderBy('expiry_date')
            ->take(10)
            ->get()
            ->map(function ($batch) {
                return [
                    'item' => $batch->item->name,
                    'batch' => $batch->batch_number,
                    'qty' => $batch->quantity_remaining,
                    'unit' => $batch->item->baseUnit->symbol ?? '',
                    'expiry' => $batch->expiry_date?->format('M d, Y'),
                    'days' => $batch->days_until_expiry,
                    'location' => $batch->storageLocation->name ?? '—',
                    'status' => $batch->expiry_status,
                ];
            })->toArray();

        // Kitchen Consumption (Last 30 days)
        $fromDate = now()->subDays(30)->toDateString();
        $toDate = now()->toDateString();

        $this->kitchenConsumption = Kitchen::active()->get()->map(function ($kitchen) use ($fromDate, $toDate) {
            $movements = StockMovement::where('kitchen_id', $kitchen->id)
                ->where('direction', 'out')
                ->whereBetween('movement_date', [$fromDate, $toDate])
                ->get();

            return [
                'name' => $kitchen->name,
                'total_qty' => $movements->sum('quantity_base'),
                'total_cost' => $movements->sum('total_cost'),
                'items_count' => $movements->pluck('item_id')->unique()->count(),
            ];
        })->sortByDesc('total_qty')->values()->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
?>

<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Dashboard</h1>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Overview of your inventory system</p>
        </div>
        <div class="flex items-center space-x-2">
            <wire:loading.class="opacity-50" wire:target="loadDashboardData">
            <button wire:click="loadDashboardData" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center space-x-2">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh</span>
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-sky-50 dark:bg-sky-900/30 rounded-lg">
                    <i class="fas fa-box text-sky-600 dark:text-sky-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_items'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Total Items</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                    <i class="fas fa-utensils text-emerald-600 dark:text-emerald-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_kitchens'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Kitchens</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <i class="fas fa-warehouse text-indigo-600 dark:text-indigo-400 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_warehouses'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Warehouses</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                    <i class="fas fa-clock text-amber-600 dark:text-amber-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['pending_vouchers'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Pending Approvals</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                    <i class="fas fa-dollar-sign text-emerald-600 dark:text-emerald-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">${{ number_format($stats['total_stock_value'], 2) }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Stock Value</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-rose-50 dark:bg-rose-900/30 rounded-lg">
                    <i class="fas fa-exclamation-triangle text-rose-600 dark:text-rose-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['out_of_stock'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Out of Stock</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                    <i class="fas fa-alert-circle text-amber-600 dark:text-amber-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['below_min'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Below Min Level</p>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
            <div class="flex items-center justify-between">
                <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-lg">
                    <i class="fas fa-calendar-times text-amber-600 dark:text-amber-300 text-xl"></i>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['expiring_soon'] }}</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Expiring Soon</p>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Stock Status Distribution --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Stock Status Overview</h3>
            <div class="h-64">
                <canvas id="stockStatusChart"></canvas>
            </div>
        </div>

        {{-- Kitchen Consumption --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Kitchen Consumption (30 Days)</h3>
            <div class="h-64">
                <canvas id="kitchenConsumptionChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Data Tables Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Movements --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Recent Stock Movements</h3>
                <a href="{{ route('stock-vouchers.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Voucher</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Kitchen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($recentMovements as $movement)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $movement['date'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 font-mono">{{ $movement['voucher'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @php
                                        $type = $movement['type'];
                                        echo match($type) {
                                            'Stock In' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                                            'Stock Out' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                            'Transfer' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                                            'Adjustment' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                            default => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                        };
                                    @endphp
                                ">{{ $movement['type'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $movement['item'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 font-mono">{{ $movement['qty'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $movement['kitchen'] }}</td>
                        </tr>
                        @endforeach
                        @if(empty($recentMovements))
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No recent movements</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Low Stock / Reorder Needed</h3>
                <a href="{{ route('stock-control.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Current</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Min Level</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Reorder Pt</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($lowStockItems as $item)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $item['name'] }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $item['sku'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ number_format($item['current'], 2) }} {{ $item['unit'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ number_format($item['min'], 2) }} {{ $item['unit'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ number_format($item['reorder'], 2) }} {{ $item['unit'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @php
                                        $status = $item['status']['status'];
                                        echo match($status) {
                                            'out_of_stock' => 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
                                            'below_min' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                            'needs_reorder' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
                                            default => 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-300',
                                        };
                                    @endphp
                                ">{{ $item['status']['label'] }}</span>
                            </td>
                        </tr>
                        @endforeach
                        @if(empty($lowStockItems))
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">All items at optimal levels</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Expiring Soon & Kitchen Consumption --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Expiring Soon --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Expiring Soon (7 Days)</h3>
                <a href="{{ route('tracking.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Batch</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Expiry</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Days</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Location</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($expiringSoonItems as $batch)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 text-sm text-slate-900 dark:text-white">{{ $batch['item'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300 font-mono">{{ $batch['batch'] }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">{{ number_format($batch['qty'], 2) }} {{ $batch['unit'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $batch['expiry'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    @if($batch['days'] <= 0) bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300
                                    @elseif($batch['days'] <= 3) bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @else bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300
                                    @endif
                                ">{{ $batch['days'] }} days</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $batch['location'] }}</td>
                        </tr>
                        @endforeach
                        @if(empty($expiringSoonItems))
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No items expiring soon</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Kitchen Consumption --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Kitchen Consumption (30 Days)</h3>
                <a href="{{ route('reports.consumption') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">View Report</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Kitchen</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Qty</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Total Cost</th>
                            <th class="px-4 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($kitchenConsumption as $kitchen)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-slate-900 dark:text-white">{{ $kitchen['name'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ number_format($kitchen['total_qty'], 2) }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-slate-900 dark:text-white">${{ number_format($kitchen['total_cost'], 2) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">{{ $kitchen['items_count'] }}</td>
                        </tr>
                        @endforeach
                        @if(empty($kitchenConsumption))
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No consumption data</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Stock Status Chart
    const stockStatusCtx = document.getElementById('stockStatusChart');
    if (stockStatusCtx) {
        new Chart(stockStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Optimal', 'Needs Reorder', 'Below Min', 'Out of Stock', 'Overstocked'],
                datasets: [{
                    data: [
                        {{ collect($lowStockItems)->where('status.status', 'optimal')->count() ?: ($stats['total_items'] - $stats['out_of_stock'] - $stats['below_min'] - $stats['expiring_soon']) }},
                        {{ collect($lowStockItems)->where('status.status', 'needs_reorder')->count() }},
                        {{ collect($lowStockItems)->where('status.status', 'below_min')->count() }},
                        {{ collect($lowStockItems)->where('status.status', 'out_of_stock')->count() }},
                        0
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } }
                }
            }
        });
    }

    // Kitchen Consumption Chart
    const kitchenCtx = document.getElementById('kitchenConsumptionChart');
    if (kitchenCtx && {{ count($kitchenConsumption) > 0 }}) {
        new Chart(kitchenCtx, {
            type: 'bar',
            data: {
                labels: {{ json_encode(array_column($kitchenConsumption, 'name')) }},
                datasets: [{
                    label: 'Quantity Consumed',
                    data: {{ json_encode(array_column($kitchenConsumption, 'total_qty')) }},
                    backgroundColor: 'rgba(14, 165, 233, 0.8)',
                    borderColor: 'rgb(14, 165, 233)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush






