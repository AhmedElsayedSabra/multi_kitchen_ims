<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

class TestRoutes extends Command
{
    protected $signature = 'test:routes';

    protected $description = 'Test all web routes return 200 for authenticated admin user';

    public function handle(): int
    {
        $user = User::where('email', 'admin@inventory.com')->first();
        if (! $user) {
            $this->error('No admin user found in database');

            return 1;
        }

        $this->info("Testing as user: {$user->name} ({$user->email})");
        $this->info('Roles: '.$user->getRoleNames()->implode(', '));

        // Get all web routes
        $routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
        $webRoutes = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            $name = $route->getName();

            if (str_starts_with($uri, 'livewire-') ||
                str_starts_with($uri, 'storage/') ||
                str_starts_with($uri, 'wireui/') ||
                $uri === 'up') {
                continue;
            }

            $webRoutes[] = [
                'uri' => $uri,
                'name' => $name,
            ];
        }

        $this->info('Found '.count($webRoutes)." web routes to test:\n");

        $results = [];
        $kernel = $this->laravel->make(Kernel::class);

        // Properly authenticate the user
        Auth::login($user);

        foreach ($webRoutes as $route) {
            $uri = '/'.ltrim($route['uri'], '/');

            try {
                $request = Request::create($uri, 'GET');
                $request->setLaravelSession($this->laravel['session']->driver());

                $response = $kernel->handle($request);
                $status = $response->getStatusCode();
                $content = $response->getContent();

                $results[] = [
                    'uri' => $uri,
                    'name' => $route['name'],
                    'status' => $status,
                    'success' => $status === 200,
                ];

                $statusIcon = $status === 200 ? '✓' : '✗';
                $this->line("{$statusIcon} {$route['name']} ({$uri}) -> {$status}");

                if ($status >= 400) {
                    // Extract error message from response
                    $errorMsg = '';
                    if (str_contains($content, '<title>')) {
                        // HTML error page - extract title
                        if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                            $errorMsg = $matches[1];
                        }
                    } else {
                        $errorMsg = substr(strip_tags($content), 0, 200);
                    }
                    if ($errorMsg) {
                        $this->error("    Page title: {$errorMsg}");
                    }

                    // Show first 500 chars of content for debugging
                    $this->line('    Content preview: '.substr($content, 0, 500));
                }

            } catch (Throwable $e) {
                $results[] = [
                    'uri' => $uri,
                    'name' => $route['name'],
                    'status' => 'ERROR',
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
                $this->error("✗ {$route['name']} ({$uri}) -> ERROR: {$e->getMessage()}");
                $this->error("    File: {$e->getFile()}:{$e->getLine()}");
            }
        }

        $this->newLine();
        $this->info(str_repeat('=', 80));
        $this->info('SUMMARY');
        $this->info(str_repeat('=', 80));

        $passed = array_filter($results, fn ($r) => $r['success']);
        $failed = array_filter($results, fn ($r) => ! $r['success']);

        $this->info('Passed: '.count($passed));
        $this->info('Failed: '.count($failed));

        if (count($failed) > 0) {
            $this->newLine();
            $this->error('FAILED ROUTES:');
            foreach ($failed as $f) {
                $this->error("  - {$f['name']} ({$f['uri']}) -> {$f['status']}".(isset($f['error']) ? " ({$f['error']})" : ''));
            }

            return 1;
        } else {
            $this->newLine();
            $this->info('All routes returned HTTP 200!');

            return 0;
        }
    }
}
