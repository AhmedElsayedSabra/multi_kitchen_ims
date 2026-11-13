<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Notifications
    Route::get('/notifications', function () {
        return view('notifications.index');
    })->name('notifications.index');

    // System Settings (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::livewire('/settings', 'inventory::settings.index')->name('settings');
        Route::livewire('/users', 'inventory::users.index')->name('users.index');
    });

    // Warehouses
    Route::livewire('/warehouses', 'inventory::warehouses.index')->name('warehouses.index');

    // Kitchens
    Route::livewire('/kitchens', 'inventory::kitchens.index')->name('kitchens.index');

    // Storage Locations
    Route::livewire('/storage-locations', 'inventory::storage-locations.index')->name('storage-locations.index');

    // Unit Categories
    Route::livewire('/unit-categories', 'inventory::unit-categories.index')->name('unit-categories.index');

    // Units
    Route::livewire('/units', 'inventory::units.index')->name('units.index');

    // Categories
    Route::livewire('/categories', 'inventory::categories.index')->name('categories.index');

    // Items
    Route::livewire('/items', 'inventory::items.index')->name('items.index');

    // Stock Vouchers
    Route::livewire('/stock-vouchers', 'inventory::stock-vouchers.index')->name('stock-vouchers.index');

    // Stock Voucher Types (for sidebar links) - redirect to stock-vouchers with type filter
    Route::get('/stock-vouchers/addition/create', function () {
        return redirect()->route('stock-vouchers.index', ['type' => 'addition', 'create' => true]);
    })->name('stock.additions.create');
    Route::get('/stock-vouchers/issue/create', function () {
        return redirect()->route('stock-vouchers.index', ['type' => 'issue', 'create' => true]);
    })->name('stock.issues.create');
    Route::get('/stock-vouchers/return/create', function () {
        return redirect()->route('stock-vouchers.index', ['type' => 'return', 'create' => true]);
    })->name('stock.returns.create');
    Route::get('/stock-vouchers/transfer/create', function () {
        return redirect()->route('stock-vouchers.index', ['type' => 'transfer', 'create' => true]);
    })->name('stock.transfers.create');
    Route::get('/stock-vouchers/adjustment/create', function () {
        return redirect()->route('stock-vouchers.index', ['type' => 'adjustment', 'create' => true]);
    })->name('stock.adjustments.create');

    // Stock Control
    Route::livewire('/stock-control', 'inventory::stock-control.index')->name('stock-control.index');

    // Batch Tracking
    Route::livewire('/tracking', 'inventory::tracking.index')->name('tracking.index');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::livewire('/stock', 'inventory::reports.stock')->name('stock');
        Route::livewire('/consumption', 'inventory::reports.consumption')->name('consumption');
        Route::livewire('/movements', 'inventory::reports.movements')->name('movements');
        Route::livewire('/valuation', 'inventory::reports.valuation')->name('valuation');
        Route::livewire('/expiry', 'inventory::reports.expiry')->name('expiry');
    });

    // Audit Logs (Admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/audit-logs', function () {
            return view('audit-logs.index');
        })->name('audit-logs.index');
    });
});
