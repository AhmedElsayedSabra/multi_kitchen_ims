<aside
    x-data="{ activeMenu: @js(request()->routeIs('reports.*') ? 'reports' : (request()->routeIs('stock-vouchers.*', 'stock.*') ? 'stock' : '')) }"
    style="width: 260px; position: fixed; top: 0; bottom: 0; inset-inline-start: 0; z-index: 40; display: flex; flex-direction: column; background-color: #0f172a; color: #e2e8f0;"
    class="app-sidebar -translate-x-full flex flex-col lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    aria-label="Application sidebar"
>
    <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-6" aria-label="{{ $settings['system_name'] ?? config('app.name') }} home">
        @if(!empty($settings['logo_url']))
            <img src="{{ $settings['logo_url'] }}" alt="" class="h-9 w-auto max-w-36 object-contain">
        @else
            <span class="grid size-9 place-items-center rounded-xl bg-indigo-600 text-white">
                <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z" stroke-width="1.8" stroke-linejoin="round"/>
                    <path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            </span>
            <span class="truncate text-base font-semibold tracking-tight text-slate-100">{{ $settings['system_name'] ?? config('app.name', 'IMS') }}</span>
        @endif
    </a>

    <nav class="flex-1 space-y-6 overflow-y-auto overflow-x-hidden p-4" aria-label="Main navigation">
        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Workspace</p>
            <a href="{{ route('dashboard') }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 rounded-lg border-e-4 px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('dashboard') ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950 dark:text-indigo-300' : 'border-transparent text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="m3.75 10.5 8.25-6.75 8.25 6.75v9a.75.75 0 0 1-.75.75h-5.25v-6h-4.5v6H4.5a.75.75 0 0 1-.75-.75v-9Z" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Inventory</p>
            <button type="button" @click="activeMenu = activeMenu === 'stock' ? '' : 'stock'"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm text-slate-300 transition-colors hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                    :aria-expanded="activeMenu === 'stock'">
                <span class="flex items-center gap-3">
                    <svg width="20" height="20" class="w-5 h-5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="m4 7.5 8-4.5 8 4.5v9L12 21l-8-4.5v-9Z" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="m4.5 7.8 7.5 4.3 7.5-4.3M12 12v8.5" stroke-width="1.7" stroke-linejoin="round"/>
                    </svg>
                    <span>Stock Management</span>
                </span>
                <svg width="16" height="16" class="w-4 h-4 shrink-0 transition-transform" :class="activeMenu === 'stock' ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div x-cloak x-show="activeMenu === 'stock'" x-transition class="space-y-1 ps-8">
                <a href="{{ route('stock-vouchers.index') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('stock-vouchers.index') ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">All Vouchers</a>
                <a href="{{ route('stock.additions.create') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">Addition</a>
                <a href="{{ route('stock.issues.create') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">Issue</a>
                <a href="{{ route('stock.returns.create') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">Return</a>
                <a href="{{ route('stock.transfers.create') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">Transfer</a>
                <a href="{{ route('stock.adjustments.create') }}" @click="sidebarOpen = false" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">Adjustment</a>
            </div>

            <a href="{{ route('stock-control.index') }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 rounded-lg border-e-4 px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('stock-control.*') ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950 dark:text-indigo-300' : 'border-transparent text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M4 19V5m0 14h16M7 15l4-4 3 2 5-6" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Stock Control & Reordering</span>
            </a>
            <a href="{{ route('tracking.index') }}" @click="sidebarOpen = false"
               class="flex items-center gap-3 rounded-lg border-e-4 px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('tracking.*') ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950 dark:text-indigo-300' : 'border-transparent text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <circle cx="12" cy="12" r="8.25" stroke-width="1.7"/>
                    <path d="M12 7.5v5l3 1.75" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Batch & Expiry Tracking</span>
            </a>
        </div>

        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Master Data</p>
            @foreach([
                ['kitchens.index', 'Kitchens', 'M3.75 20.25h16.5M4.5 20.25V9l7.5-5.25L19.5 9v11.25M9 20.25v-6h6v6'],
                ['warehouses.index', 'Warehouses', 'M3.75 20.25h16.5V8.5L12 3.75 3.75 8.5v11.75ZM8 20.25v-6h8v6M8 9.5h.01M12 9.5h.01M16 9.5h.01'],
                ['storage-locations.index', 'Storage Locations', 'M12 21s7.5-5.25 7.5-11.25a7.5 7.5 0 1 0-15 0C4.5 15.75 12 21 12 21Z M12 12.5a2.75 2.75 0 1 0 0-5.5 2.75 2.75 2.75 0 0 0 0 5.5Z'],
                ['unit-categories.index', 'Unit Categories', 'M4 5.5h16M4 12h16M4 18.5h16M8 3.5v4M16 10v4M10 16.5v4'],
                ['units.index', 'Units', 'M7 3.75h10M7 20.25h10M9 3.75v4.5l3 3.75-3 3.75v4.5M15 3.75v4.5L12 12l3 3.75v4.5'],
                ['categories.index', 'Categories', 'M4 6.75h16M4 12h16M4 17.25h16M7 4.5v4.5M17 9.75v4.5M10 15v4.5'],
                ['items.index', 'Items / Products', 'm4 7.5 8-4.5 8 4.5v9L12 21l-8-4.5v-9ZM4.5 7.8l7.5 4.3 7.5-4.3M12 12v8.5'],
            ] as [$route, $label, $icon])
                <a href="{{ route($route) }}" @click="sidebarOpen = false"
                   class="flex items-center gap-3 rounded-lg border-e-4 px-3 py-2.5 text-sm transition-colors {{ request()->routeIs(str_replace('.index', '*', $route)) ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950 dark:text-indigo-300' : 'border-transparent text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                    <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="{{ $icon }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>

        <div class="space-y-1">
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Reports</p>
            <button type="button" @click="activeMenu = activeMenu === 'reports' ? '' : 'reports'"
                    class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm transition-colors {{ request()->routeIs('reports.*') ? 'bg-indigo-50 font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}"
                    :aria-expanded="activeMenu === 'reports'">
                <span class="flex items-center gap-3">
                    <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M4.5 19.5V12m5 7.5V7m5 12.5v-5m5 5V4.5" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                    <span>Reports</span>
                </span>
                <svg width="16" height="16" class="w-4 h-4 shrink-0 transition-transform" :class="activeMenu === 'reports' ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                </svg>
            </button>
            <div x-cloak x-show="activeMenu === 'reports'" x-transition class="space-y-1 ps-8">
                @foreach([
                    ['reports.stock', 'Current Stock'],
                    ['reports.consumption', 'Kitchen Consumption'],
                    ['reports.movements', 'Stock Movements'],
                    ['reports.valuation', 'Stock Valuation'],
                    ['reports.expiry', 'Expiry Tracking'],
                ] as [$route, $label])
                    <a href="{{ route($route) }}" @click="sidebarOpen = false"
                       class="block rounded-lg px-3 py-2 text-sm transition-colors {{ request()->routeIs($route) ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if(auth()->user()->hasRole('admin') || auth()->user()->can('manage users'))
            <div class="space-y-1 border-t border-slate-800 pt-5">
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Administration</p>
                @foreach([
                    ['users.index', 'Users & Roles', 'M16 18.75h5.25v-1.5a3.75 3.75 0 0 0-6-3M16 18.75H8m8 0v-1.5c0-1.1-.47-2.1-1.22-2.8M8 18.75H2.75v-1.5a3.75 3.75 0 0 1 6-3M8 18.75v-1.5c0-1.1.47-2.1 1.22-2.8m0 0a4.5 4.5 0 1 1 5.56 0'],
                    ['settings', 'System Settings', 'M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 3.75 0 0 0 0-7.5ZM19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-1.5 2.6-.08-.02a1.7 1.7 0 0 0-1.78.63l-.05.07h-3l-.04-.07a1.7 1.7 0 0 0-1.78-.63l-.08.02-1.5-2.6.06-.06A1.7 1.7 0 0 0 10.4 15l-.09-.04v-3l.09-.04a1.7 1.7 0 0 0-.34-1.88l-.06-.06 1.5-2.6-.08.02a1.7 1.7 0 0 0-1.78-.63l-.05-.07h-3l-.04.07a1.7 1.7 0 0 0-1.78.63l-.08-.02-1.5 2.6.06.06A1.7 1.7 0 0 0 10.4 15l-.09.04v3l.09.04a1.7 1.7 0 0 0-.34 1.88l.06.06 1.5 2.6.08-.02a1.7 1.7 0 0 0 1.78.63l.04.07h3l.05-.07a1.7 1.7 0 0 0 1.78-.63l.08.02 1.5-2.6-.06-.06A1.7 1.7 0 0 0 19.4 15Z'],
                    ['audit-logs.index', 'Audit Logs', 'M4.5 4.5h15v15h-15zM8 8.25h8M8 12h8M8 15.75h5'],
                ] as [$route, $label, $icon])
                    <a href="{{ route($route) }}" @click="sidebarOpen = false"
                       class="flex items-center gap-3 rounded-lg border-e-4 px-3 py-2.5 text-sm transition-colors {{ request()->routeIs(str_replace('.index', '*', $route)) ? 'border-indigo-600 bg-indigo-50 font-semibold text-indigo-700 dark:border-indigo-400 dark:bg-indigo-950 dark:text-indigo-300' : 'border-transparent text-slate-300 hover:bg-slate-800/50 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white' }}">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="{{ $icon }}" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </nav>

    <div class="shrink-0 border-t border-slate-800 px-5 py-4">
        <p class="truncate text-center text-xs font-medium text-slate-400">{{ $settings['company_name'] ?? '' }}</p>
        <p class="mt-1 text-center text-[11px] text-slate-500">Inventory Management System</p>
    </div>
</aside>