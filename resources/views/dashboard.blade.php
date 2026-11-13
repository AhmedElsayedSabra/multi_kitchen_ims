@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h2 class="text-sm font-medium text-slate-600">Welcome back, {{ auth()->user()->name }}. Here's an overview of your inventory.</h2>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-sky-50 text-sky-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">Total Items</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $totalItems }}</p>
                <p class="mt-1 text-xs text-slate-500">Items in the inventory</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">Warehouses</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $totalWarehouses }}</p>
                <p class="mt-1 text-xs text-slate-500">Storage sites configured</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">Kitchens</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $totalKitchens }}</p>
                <p class="mt-1 text-xs text-slate-500">Production sites configured</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
        <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-500">Low Stock Items</p>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">{{ $lowStockItems->count() }}</p>
                <p class="mt-1 text-xs text-amber-700">Require reorder attention</p>
            </div>
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Low Stock Items -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Low Stock Items</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($lowStockItems as $item)
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-900">{{ $item->name }}</p>
                        <p class="text-sm text-slate-500">SKU: {{ $item->sku }} | Current: {{ $item->current_stock }} {{ $item->baseUnit->symbol }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full border border-rose-200 bg-rose-50 text-rose-700">Low Stock</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500">No low stock items</div>
            @endforelse
        </div>
    </div>

    <!-- Expiring Soon -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Expiring Soon (30 days)</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($expiringSoon as $batch)
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-900">{{ $batch->item->name }}</p>
                        <p class="text-sm text-slate-500">Batch: {{ $batch->batch_number }} | Expires: {{ $batch->expiry_date->format('M d, Y') }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $batch->isExpired() ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ $batch->isExpired() ? 'Expired' : $batch->days_until_expiry . ' days' }}
                    </span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500">No expiring batches</div>
            @endforelse
        </div>
    </div>
</div>

<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <!-- Recent Movements -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="px-6 py-4 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Recent Stock Movements</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($recentMovements as $movement)
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-slate-900">{{ $movement->item->name }}</p>
                        <p class="text-sm text-slate-500">{{ $movement->voucher->voucher_type }} | {{ $movement->movement_date->format('M d, Y H:i') }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $movement->direction === 'in' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $movement->direction === 'in' ? '+' : '-' }}{{ $movement->quantity }} {{ $movement->unit->symbol }}
                    </span>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500">No recent movements</div>
            @endforelse
        </div>
    </div>

    <!-- Monthly Chart (Placeholder) -->
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Monthly Stock Value (Last 6 Months)</h2>
        <div class="space-y-4">
            @foreach ($monthlyMovements as $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium">{{ $data['month'] }}</span>
                        <span class="text-emerald-600">+{{ number_format($data['in'], 2) }}</span>
                        <span class="text-rose-600">-{{ number_format($data['out'], 2) }}</span>
                    </div>
                    <div class="h-2 bg-slate-200 rounded overflow-hidden">
                        @php
                            $total = $data['in'] + $data['out'];
                            $inPercent = $total > 0 ? ($data['in'] / $total) * 100 : 0;
                        @endphp
                        <div class="h-full bg-emerald-500" style="width: {{ $inPercent }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('print-report', () => {
            window.print();
        });
    });
</script>
@endpush






