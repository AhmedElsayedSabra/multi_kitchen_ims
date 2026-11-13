<nav x-data="{ mobileMenuOpen: false }" class="fixed top-0 start-0 end-0 z-50 bg-white border-b border-slate-200 lg:hidden">
    <div class="flex items-center justify-between h-16 px-4">
        {{-- Mobile Menu Button --}}
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Toggle menu">
            <svg width="20" height="20" class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path d="M4 6.75h16M4 12h16M4 17.25h16" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>

        {{-- Logo --}}
        <div class="flex items-center flex-1 lg:hidden">
            @if($settings['logo'])
                <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['system_name'] }}" class="h-8 w-auto">
            @else
                <span class="text-xl font-bold text-indigo-600">{{ $settings['system_name'] }}</span>
            @endif
        </div>

        {{-- User Menu (Mobile) --}}
        <div class="lg:hidden">
            <div class="relative" x-data="{ userMenuOpen: false }">
                <button @click="userMenuOpen = !userMenuOpen" class="flex items-center space-x-2 p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center">
                        <svg width="20" height="20" class="w-5 h-5 shrink-0 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>
                <div x-cloak x-show="userMenuOpen" @click.outside="userMenuOpen = false" class="absolute end-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-slate-200 py-1 z-50">
                    <div class="px-4 py-2 border-b border-slate-200">
                        <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Profile</a>
                    <a href="{{ route('settings') }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100">Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-start px-4 py-2 text-sm text-rose-600 hover:bg-slate-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Mobile Sidebar --}}
    <div x-cloak x-show="mobileMenuOpen" @click.outside="mobileMenuOpen = false" x-transition class="lg:hidden fixed inset-0 z-40 bg-black/50"></div>
    <div x-cloak x-show="mobileMenuOpen" x-transition class="lg:hidden fixed inset-y-0 start-0 z-50 w-64 bg-white shadow-xl overflow-y-auto">
        @livewire('layouts.sidebar')
    </div>
</nav>