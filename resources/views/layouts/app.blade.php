<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ str_starts_with(app()->getLocale(), 'ar') ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'IMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        aside svg, nav svg { width: 1.25rem !important; height: 1.25rem !important; flex-shrink: 0 !important; }
        input:not([type="checkbox"]):not([type="radio"]), select, textarea,
        select option, option {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
            border-radius: 0.5rem !important;
            font-size: 0.875rem !important;
        }
        @media (min-width: 1024px) {
            .app-sidebar {
                display: flex !important;
                visibility: visible !important;
                transform: none !important;
                translate: 0 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" x-data="{ sidebarOpen: false }">
    <div
        x-cloak
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-950/40 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    @livewire('layouts.sidebar')

    <div style="margin-inline-start: 260px; min-height: 100vh; display: flex; flex-direction: column; background-color: #f8fafc;" class="dark:bg-slate-950">
        @livewire('layouts.topbar', ['title' => $title ?? null])

        <main style="flex: 1; padding: 1.5rem;">
            <div class="mx-auto w-full max-w-7xl">
                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>
    </div>

    @livewire('layouts.modals')

    @livewireScripts
    @stack('scripts')
</body>
</html>