@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">Profile Settings</h2>

        @if(session('status'))
            <div class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @error('name')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                @error('email')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <hr class="border-slate-200 dark:border-slate-700">

            {{-- Current Password --}}
            <div>
                <label for="current_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Current Password</label>
                <input type="password" name="current_password" id="current_password"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autocomplete="current-password">
                @error('current_password')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Required if changing password.</p>
            </div>

            {{-- New Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">New Password</label>
                <input type="password" name="password" id="password"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autocomplete="new-password">
                @error('password')
                    <p class="mt-1 text-sm text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirm Password --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autocomplete="new-password">
            </div>

            {{-- Save Button --}}
            <div class="flex justify-end space-x-3 pt-4">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800">
                    Save Changes
                </button>
            </div>
        </form>

        <hr class="my-6 border-slate-200 dark:border-slate-700">

        {{-- Danger Zone --}}
        <div class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg p-6">
            <h3 class="text-lg font-medium text-rose-700 dark:text-rose-300 mb-2">Danger Zone</h3>
            <p class="text-rose-700 dark:text-rose-300 mb-4">Once you delete your account, there is no going back. Please be certain.</p>
            <form method="POST" action="{{ route('profile.destroy') }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                    onclick="return confirm('Are you sure you want to delete your account? This action is irreversible.');">
                    Delete Account
                </button>
            </form>
        </div>
    </div>
</div>
@endsection







