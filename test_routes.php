<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

// Set the base URL
URL::forceRootUrl('http://localhost:8080');

// Get the first user (should be admin from seeder)
$user = User::first();
if (! $user) {
    echo "ERROR: No user found in database\n";
    exit(1);
}

echo "Testing as user: {$user->name} ({$user->email})\n";
echo 'Roles: '.$user->getRoleNames()->implode(', ')."\n\n";

// Get all web routes (excluding livewire internal routes)
$routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
$webRoutes = [];

foreach ($routes as $route) {
    $uri = $route->uri();
    $name = $route->getName();

    // Skip livewire internal routes and storage routes
    if (str_starts_with($uri, 'livewire-') ||
        str_starts_with($uri, 'storage/') ||
        str_starts_with($uri, 'wireui/') ||
        $uri === 'up') {
        continue;
    }

    $webRoutes[] = [
        'uri' => $uri,
        'name' => $name,
        'action' => $route->getActionName(),
    ];
}

echo 'Found '.count($webRoutes)." web routes to test:\n\n";

$results = [];

// For each route, make a request as authenticated user
foreach ($webRoutes as $route) {
    $uri = '/'.ltrim($route['uri'], '/');

    try {
        // Create a request
        $request = Request::create($uri, 'GET');

        // Set the user on the request
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Start session
        $session = $app['session']->driver();
        $request->setLaravelSession($session);

        // Dispatch the request
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();

        $results[] = [
            'uri' => $uri,
            'name' => $route['name'],
            'status' => $status,
            'success' => $status === 200,
        ];

        $statusIcon = $status === 200 ? '✓' : '✗';
        echo "{$statusIcon} {$route['name']} ({$uri}) -> {$status}\n";

    } catch (Exception $e) {
        $results[] = [
            'uri' => $uri,
            'name' => $route['name'],
            'status' => 'ERROR',
            'success' => false,
            'error' => $e->getMessage(),
        ];
        echo "✗ {$route['name']} ({$uri}) -> ERROR: {$e->getMessage()}\n";
    }
}

// Summary
echo "\n".str_repeat('=', 80)."\n";
echo "SUMMARY\n";
echo str_repeat('=', 80)."\n";

$passed = array_filter($results, fn ($r) => $r['success']);
$failed = array_filter($results, fn ($r) => ! $r['success']);

echo 'Passed: '.count($passed)."\n";
echo 'Failed: '.count($failed)."\n";

if (count($failed) > 0) {
    echo "\nFAILED ROUTES:\n";
    foreach ($failed as $f) {
        echo "  - {$f['name']} ({$f['uri']}) -> {$f['status']}";
        if (isset($f['error'])) {
            echo " ({$f['error']})";
        }
        echo "\n";
    }
    exit(1);
} else {
    echo "\nAll routes returned HTTP 200!\n";
    exit(0);
}
