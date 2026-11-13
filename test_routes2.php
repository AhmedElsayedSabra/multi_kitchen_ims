<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Test route generation directly
$router = $app->make('router');

$routes = [
    'dashboard',
    'profile.edit',
    'profile.update',
    'profile.destroy',
    'notifications.index',
    'settings',
    'users.index',
    'warehouses.index',
    'kitchens.index',
    'storage-locations.index',
    'unit-categories.index',
    'units.index',
    'categories.index',
    'items.index',
    'stock-vouchers.index',
    'stock-control.index',
    'tracking.index',
    'reports.stock',
    'reports.consumption',
    'reports.movements',
    'reports.valuation',
    'reports.expiry',
    'audit-logs.index',
    'stock.additions.create',
    'stock.issues.create',
    'stock.returns.create',
    'stock.transfers.create',
    'stock.adjustments.create',
];

echo "Testing route generation:\n";
foreach ($routes as $route) {
    try {
        $url = route($route);
        echo "✓ {$route} -> {$url}\n";
    } catch (Exception $e) {
        echo "✗ {$route} -> ERROR: ".$e->getMessage()."\n";
    }
}

// Now test HTTP requests with authenticated user
$user = User::where('email', 'admin@inventory.com')->first();

if (! $user) {
    echo "\nAdmin user not found!\n";
    exit(1);
}

echo "\n\nTesting HTTP requests (authenticated):\n";

// Login the user via session
$request = Request::create('/dashboard', 'GET');
$request->setUserResolver(fn () => $user);

// Set up session
$session = $app->make('session.store');
$session->put('login_web_'.sha1('web'), $user->getAuthIdentifier());
$session->put('login_web_'.sha1('web').'_remember', true);
$session->save();

$request->setLaravelSession($session);

$response = $app->handle($request);
echo 'Dashboard: '.$response->getStatusCode()."\n";

// Test other routes
$testUrls = [
    '/settings' => 'settings',
    '/users' => 'users.index',
    '/warehouses' => 'warehouses.index',
    '/kitchens' => 'kitchens.index',
    '/storage-locations' => 'storage-locations.index',
    '/unit-categories' => 'unit-categories.index',
    '/units' => 'units.index',
    '/categories' => 'categories.index',
    '/items' => 'items.index',
    '/stock-vouchers' => 'stock-vouchers.index',
    '/stock-control' => 'stock-control.index',
    '/tracking' => 'tracking.index',
    '/reports/stock' => 'reports.stock',
    '/reports/consumption' => 'reports.consumption',
    '/reports/movements' => 'reports.movements',
    '/reports/valuation' => 'reports.valuation',
    '/reports/expiry' => 'reports.expiry',
    '/audit-logs' => 'audit-logs.index',
];

foreach ($testUrls as $url => $name) {
    $request = Request::create($url, 'GET');
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($session);

    $response = $app->handle($request);
    $status = $response->getStatusCode();
    $icon = $status === 200 ? '✓' : '✗';
    echo "{$icon} {$name} ({$url}): {$status}\n";
}
