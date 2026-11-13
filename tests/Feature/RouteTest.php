<?php

namespace Tests\Feature;

use App\Models\StockVoucher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageUsers = Permission::create([
            'name' => 'manage users',
            'guard_name' => 'web',
        ]);
        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin->givePermissionTo($manageUsers);

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->assertSame(1, $this->user->id);
        $this->user->assignRole($admin);
    }

    #[DataProvider('webRoutes')]
    public function test_web_route_returns_main_page_content(string $name, string $uri): void
    {
        $response = $this->followingRedirects()
            ->actingAs($this->user)
            ->get($uri);

        $response->assertOk();
        $this->assertSame(
            1,
            preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $response->getContent(), $main),
            "Route {$name} ({$uri}) did not render a main element."
        );
        $this->assertMatchesRegularExpression(
            '/<h[1-6]\b[^>]*>\s*\S/i',
            $main[1],
            "Route {$name} ({$uri}) did not render page content inside <main>."
        );
    }

    public function test_sidebar_includes_all_requested_navigation_links(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('<aside', false);
        $response->assertSee('class="app-sidebar', false);
        $response->assertSee('color: #e2e8f0', false);
        $response->assertSee('overflow-y-auto', false);
        $response->assertSee('translate: 0 0 !important', false);

        foreach ([
            'dashboard',
            'kitchens.index',
            'warehouses.index',
            'storage-locations.index',
            'unit-categories.index',
            'units.index',
            'categories.index',
            'items.index',
            'stock-vouchers.index',
            'stock.additions.create',
            'stock.issues.create',
            'stock.returns.create',
            'stock.transfers.create',
            'stock.adjustments.create',
            'stock-control.index',
            'tracking.index',
            'reports.stock',
            'reports.consumption',
            'reports.movements',
            'reports.valuation',
            'reports.expiry',
            'users.index',
            'settings',
            'audit-logs.index',
        ] as $routeName) {
            $response->assertSee('href="'.route($routeName).'"', false);
        }

    }

    public function test_stock_voucher_type_query_filters_displayed_vouchers(): void
    {
        StockVoucher::create([
            'voucher_number' => 'ADD-1001',
            'voucher_type' => 'addition',
            'transaction_date' => '2026-11-11',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);
        StockVoucher::create([
            'voucher_number' => 'ISS-1001',
            'voucher_type' => 'issue',
            'transaction_date' => '2026-11-11',
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get('/stock-vouchers?type=issue');

        $response->assertOk()
            ->assertSee('ISS-1001')
            ->assertDontSee('ADD-1001');
    }

    public static function webRoutes(): array
    {
        return [
            ['dashboard', '/dashboard'],
            ['kitchens.index', '/kitchens'],
            ['warehouses.index', '/warehouses'],
            ['storage-locations.index', '/storage-locations'],
            ['unit-categories.index', '/unit-categories'],
            ['units.index', '/units'],
            ['categories.index', '/categories'],
            ['items.index', '/items'],
            ['stock-vouchers.index', '/stock-vouchers'],
            ['stock-vouchers.addition-filter', '/stock-vouchers?type=addition'],
            ['stock-vouchers.issue-filter', '/stock-vouchers?type=issue'],
            ['stock-vouchers.return-filter', '/stock-vouchers?type=return'],
            ['stock-vouchers.transfer-filter', '/stock-vouchers?type=transfer'],
            ['stock-vouchers.adjustment-filter', '/stock-vouchers?type=adjustment'],
            ['stock.additions.create', '/stock-vouchers/addition/create'],
            ['stock.issues.create', '/stock-vouchers/issue/create'],
            ['stock.returns.create', '/stock-vouchers/return/create'],
            ['stock.transfers.create', '/stock-vouchers/transfer/create'],
            ['stock.adjustments.create', '/stock-vouchers/adjustment/create'],
            ['stock-control.index', '/stock-control'],
            ['tracking.index', '/tracking'],
            ['reports.stock', '/reports/stock'],
            ['reports.consumption', '/reports/consumption'],
            ['reports.movements', '/reports/movements'],
            ['reports.valuation', '/reports/valuation'],
            ['reports.expiry', '/reports/expiry'],
            ['users.index', '/users'],
            ['settings', '/settings'],
            ['audit-logs.index', '/audit-logs'],
        ];
    }
}
