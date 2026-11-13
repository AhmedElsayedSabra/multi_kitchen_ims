@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Audit Logs</h2>
            <p class="text-slate-500 dark:text-slate-400 mt-1">Track system activities and changes</p>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-700">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80">
                        <tr>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Action</th>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Model</th>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3.5 text-start text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-100 dark:divide-slate-700">
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                <i class="fas fa-database text-4xl mb-2 block"></i>
                                <p>No audit logs available</p>
                                <p class="text-sm mt-1">Audit logging requires the <code>spatie/laravel-activitylog</code> package to be configured</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection







