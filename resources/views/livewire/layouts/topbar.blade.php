@php
    $pageTitle = $title ?: match (true) {
        request()->routeIs('dashboard') => 'Dashboard',
        request()->routeIs('stock-vouchers.*') => 'Stock Vouchers',
        request()->routeIs('stock-control.*') => 'Stock Control',
        request()->routeIs('tracking.*') => 'Batch & Expiry Tracking',
        request()->routeIs('reports.stock') => 'Stock Report',
        request()->routeIs('reports.consumption') => 'Consumption Report',
        request()->routeIs('reports.movements') => 'Stock Movements',
        request()->routeIs('reports.valuation') => 'Stock Valuation',
        request()->routeIs('reports.expiry') => 'Expiry Report',
        request()->routeIs('unit-categories.*') => 'Unit Categories',
        request()->routeIs('storage-locations.*') => 'Storage Locations',
        request()->routeIs('users.*') => 'Users & Roles',
        request()->routeIs('audit-logs.*') => 'Audit Logs',
        request()->routeIs('settings') => 'System Settings',
        default => \Illuminate\Support\Str::headline(request()->route()?->getName() ?? 'Dashboard'),
    };
@endphp

<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95" x-data="{ notificationsOpen: false, userMenuOpen: false }">
    <div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" @click="sidebarOpen = true" class="grid size-10 shrink-0 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-300 dark:hover:bg-slate-800" aria-label="Open navigation">
                <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path d="M4 6.75h16M4 12h16M4 17.25h16" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </button>
            <div class="min-w-0">
                <nav aria-label="Breadcrumb" class="hidden text-xs font-medium text-slate-500 sm:block dark:text-slate-400">
                    <span>Workspace</span>
                    <span class="px-1.5 text-slate-300" aria-hidden="true">/</span>
                    <span class="text-slate-700 dark:text-slate-300">{{ $pageTitle }}</span>
                </nav>
                <h1 class="truncate text-base font-semibold text-slate-900 sm:mt-0.5 sm:text-lg dark:text-slate-100">{{ $pageTitle }}</h1>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <a href="{{ route('stock-vouchers.index') }}" class="hidden items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:inline-flex">
                <svg width="16" height="16" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M10 4.25a.75.75 0 0 1 .75.75v4.25H15a.75.75 0 0 1 0 1.5h-4.25V15a.75.75 0 0 1-1.5 0v-4.25H5a.75.75 0 0 1 0-1.5h4.25V5a.75.75 0 0 1 .75-.75Z"/>
                </svg>
                <span>Stock Vouchers</span>
            </a>

            <div class="relative">
                <button type="button" @click="notificationsOpen = !notificationsOpen; userMenuOpen = false" @click.outside="notificationsOpen = false"
                        class="relative grid size-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                        aria-label="Notifications" :aria-expanded="notificationsOpen">
                    <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="absolute end-2 top-2 size-2 rounded-full bg-rose-500 ring-2 ring-white"></span>
                </button>
                <div x-cloak x-show="notificationsOpen" x-transition class="absolute end-0 z-50 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Notifications</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Inventory updates that may need your attention.</p>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        <a href="{{ route('stock-control.index') }}" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50/60 dark:hover:bg-slate-800">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full border border-amber-200 bg-amber-50 text-amber-700">
                                <svg width="16" height="16" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9.401 2.67a.75.75 0 0 1 1.198 0l7.2 10.08A.75.75 0 0 1 17.2 13.95H2.8a.75.75 0 0 1-.6-1.2l7.2-10.08ZM10 6.5a.75.75 0 0 0-.75.75v2.5a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 10 6.5Zm0 6a.875.875 0 1 0 0 1.75  .875.875 0 0 0 0-1.75Z" clip-rule="evenodd"/></svg>
                            </span>
                            <span><span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Review low stock</span><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Check items that may need reordering.</span></span>
                        </a>
                        <a href="{{ route('reports.expiry') }}" class="flex gap-3 px-4 py-3 transition hover:bg-slate-50/60 dark:hover:bg-slate-800">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full border border-rose-200 bg-rose-50 text-rose-700">
                                <svg width="16" height="16" class="w-4 h-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2.5a.75.75 0 0 1 .75.75v.51a6.25 6.25 0 1 1-6.49 6.49h-.51a.75.75 0 0 1 0-1.5h1.25a.75.75 0 0 1 .75.75 4.75 4.75 0 1 0 4.75-4.75.75.75 0 0 1-.75-.75V3.25A.75.75 0 0 1 10 2.5Z"/><path d="M9.25 6.5a.75.75 0 0 1 1.5 0v3.19l2.03 1.17a.75.75 0 1 1-.75 1.3l-2.4-1.39a.75.75 0 0 1-.38-.65V6.5Z"/></svg>
                            </span>
                            <span><span class="block text-sm font-medium text-slate-800 dark:text-slate-100">Expiry overview</span><span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">Review batches nearing their expiry date.</span></span>
                        </a>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-3 dark:border-slate-800 text-center text-sm font-medium text-indigo-600 transition hover:bg-indigo-50 dark:hover:bg-slate-800">View notifications</a>
                </div>
            </div>

            <div class="relative">
                <button type="button" @click="userMenuOpen = !userMenuOpen; notificationsOpen = false" @click.outside="userMenuOpen = false"
                        class="flex items-center gap-2 rounded-lg p-1.5 pe-2 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                        aria-label="User menu" :aria-expanded="userMenuOpen">
                    <span class="grid size-8 place-items-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="hidden text-start sm:block">
                        <span class="block max-w-36 truncate text-sm font-medium text-slate-800 dark:text-slate-100">{{ auth()->user()->name }}</span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                    </span>
                    <svg width="16" height="16" class="w-4 h-4 shrink-0 hidden text-slate-400 sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                </button>
                <div x-cloak x-show="userMenuOpen" x-transition class="absolute end-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-900/10 dark:border-slate-700 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">
                        <svg width="16" height="16" class="w-4 h-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Profile
                    </a>
                    @if(auth()->user()->hasRole('admin') || auth()->user()->can('manage users'))
                        <a href="{{ route('settings') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800">
                            <svg width="16" height="16" class="w-4 h-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M12 8.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" stroke-width="1.7"/><path d="m19.4 15 .09.04v-3l-.09-.04a1.7 1.7 0 0 1 .34-1.88l.06-.06-1.5-2.6-.08.02a1.7 1.7 0 0 1-1.78-.63l-.05-.07h-3l-.04.07a1.7 1.7 0 0 1-1.78.63l-.08-.02-1.5 2.6.06.06A1.7 1.7 0 0 1 10.4 12l-.09.04v3l.09-.04a1.7 1.7 0 0 1-.34 1.88l-.06.06 1.5 2.6.08-.02a1.7 1.7 0 0 1 1.78.63l.04.07h3l.05-.07a1.7 1.7 0 0 1 1.78-.63l.08.02 1.5-2.6-.06-.06A1.7 1.7 0 0 1 19.4 15Z" stroke-width="1.3" stroke-linejoin="round"/></svg>
                            System settings
                        </a>
                    @endif
                    <div class="my-1 border-t border-slate-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-start text-sm text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-slate-800">
                            <svg width="16" height="16" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M15.75 8.25V5.5a1 1 0 0 0-1-1h-8.5a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h8.5a1 1 0 0 0 1-1v-2.75M10.5 12h9m0 0-3.5-3.5M19.5 12 16 15.5" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>