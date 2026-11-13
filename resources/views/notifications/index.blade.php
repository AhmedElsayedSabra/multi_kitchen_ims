@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Notifications</h2>
        </div>

        <div class="p-6">
            <div class="space-y-4">
                {{-- Low Stock Alert --}}
                <a href="{{ route('stock-control.index') }}" class="block p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-700 transition-colors">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-amber-600 dark:text-amber-300"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Low Stock Alert</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">5 items below minimum level</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Click to view stock control</p>
                        </div>
                    </div>
                </a>

                {{-- Expiring Soon --}}
                <a href="{{ route('reports.expiry') }}" class="block p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-700 transition-colors">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <i class="fas fa-clock text-amber-600 dark:text-amber-300"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Expiring Soon</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">3 batches expiring within 7 days</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Click to view expiry report</p>
                        </div>
                    </div>
                </a>

                {{-- Voucher Pending Approval --}}
                <a href="{{ route('stock-vouchers.index') }}" class="block p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50/60 dark:hover:bg-slate-700 transition-colors">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center">
                            <i class="fas fa-file-alt text-sky-600 dark:text-sky-300"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">Voucher Pending Approval</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">2 vouchers awaiting approval</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Click to view vouchers</p>
                        </div>
                    </div>
                </a>

                {{-- No more notifications placeholder --}}
                <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                    <p>No more notifications</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection







