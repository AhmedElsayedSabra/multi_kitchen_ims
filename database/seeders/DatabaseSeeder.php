<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Kitchen;
use App\Models\StockMovement;
use App\Models\StockVoucher;
use App\Models\StorageLocation;
use App\Models\SystemSetting;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view dashboard',
            'manage settings',
            'manage users',
            'manage roles',
            'view warehouses',
            'manage warehouses',
            'view kitchens',
            'manage kitchens',
            'view storage locations',
            'manage storage locations',
            'view unit categories',
            'manage unit categories',
            'view units',
            'manage units',
            'view categories',
            'manage categories',
            'view items',
            'manage items',
            'view stock vouchers',
            'manage stock vouchers',
            'post stock vouchers',
            'view stock control',
            'view tracking',
            'view reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $warehouseManagerRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(Permission::all());

        $warehouseManagerRole->givePermissionTo([
            'view dashboard',
            'view warehouses',
            'manage warehouses',
            'view kitchens',
            'manage kitchens',
            'view storage locations',
            'manage storage locations',
            'view unit categories',
            'manage unit categories',
            'view units',
            'manage units',
            'view categories',
            'manage categories',
            'view items',
            'manage items',
            'view stock vouchers',
            'manage stock vouchers',
            'post stock vouchers',
            'view stock control',
            'view tracking',
            'view reports',
        ]);

        $viewerRole->givePermissionTo([
            'view dashboard',
            'view warehouses',
            'view kitchens',
            'view storage locations',
            'view unit categories',
            'view units',
            'view categories',
            'view items',
            'view stock vouchers',
            'view stock control',
            'view tracking',
            'view reports',
        ]);

        // Create users
        $admin = User::firstOrCreate(
            ['email' => 'admin@inventory.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $warehouseManager = User::firstOrCreate(
            ['email' => 'warehouse@inventory.com'],
            [
                'name' => 'Warehouse Manager',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $warehouseManager->assignRole('warehouse_manager');

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@inventory.com'],
            [
                'name' => 'Report Viewer',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $viewer->assignRole('viewer');

        // Create system settings
        $settings = [
            ['key' => 'company_name', 'value' => 'Multi-Kitchen Inventory System', 'type' => 'string', 'group' => 'general', 'label' => 'Company Name', 'description' => 'Company name displayed in headers and reports'],
            ['key' => 'company_logo', 'value' => null, 'type' => 'image', 'group' => 'general', 'label' => 'Company Logo', 'description' => 'Logo displayed in headers and print layouts'],
            ['key' => 'primary_color', 'value' => '#2563eb', 'type' => 'color', 'group' => 'general', 'label' => 'Primary Color', 'description' => 'Primary theme color (Tailwind primary-600)'],
            ['key' => 'secondary_color', 'value' => '#059669', 'type' => 'color', 'group' => 'general', 'label' => 'Secondary Color', 'description' => 'Secondary theme color'],
            ['key' => 'currency', 'value' => 'USD', 'type' => 'select', 'group' => 'general', 'label' => 'Currency', 'description' => 'Default currency for pricing', 'options' => ['USD', 'EUR', 'GBP', 'SAR', 'AED']],
            ['key' => 'currency_symbol', 'value' => '$', 'type' => 'string', 'group' => 'general', 'label' => 'Currency Symbol', 'description' => 'Symbol displayed before amounts'],
            ['key' => 'date_format', 'value' => 'M d, Y', 'type' => 'string', 'group' => 'general', 'label' => 'Date Format', 'description' => 'PHP date format for display'],
            ['key' => 'timezone', 'value' => 'UTC', 'type' => 'select', 'group' => 'general', 'label' => 'Timezone', 'description' => 'Application timezone', 'options' => ['UTC', 'America/New_York', 'Europe/London', 'Asia/Riyadh', 'Asia/Dubai']],
            ['key' => 'language', 'value' => 'en', 'type' => 'select', 'group' => 'general', 'label' => 'Default Language', 'description' => 'Default application language', 'options' => ['en' => 'English', 'ar' => 'Arabic']],
            ['key' => 'rtl_support', 'value' => true, 'type' => 'boolean', 'group' => 'general', 'label' => 'RTL Support', 'description' => 'Enable right-to-left layout for Arabic'],
            ['key' => 'barcode_prefix', 'value' => 'ITEM', 'type' => 'string', 'group' => 'inventory', 'label' => 'Barcode Prefix', 'description' => 'Prefix for auto-generated barcodes'],
            ['key' => 'auto_generate_sku', 'value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Auto-generate SKU', 'description' => 'Automatically generate SKU from item name'],
            ['key' => 'track_expiry_by_default', 'value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Track Expiry by Default', 'description' => 'Enable expiry tracking for new items'],
            ['key' => 'default_min_level', 'value' => 0, 'type' => 'number', 'group' => 'inventory', 'label' => 'Default Min Level', 'description' => 'Default minimum stock level for new items'],
            ['key' => 'default_reorder_point', 'value' => 0, 'type' => 'number', 'group' => 'inventory', 'label' => 'Default Reorder Point', 'description' => 'Default reorder point for new items'],
            ['key' => 'allow_negative_stock', 'value' => false, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Allow Negative Stock', 'description' => 'Allow stock to go negative during issues'],
            ['key' => 'fefo_enabled', 'value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'FEFO Selection', 'description' => 'Use First Expired First Out for stock issues'],
            ['key' => 'auto_create_batch', 'value' => true, 'type' => 'boolean', 'group' => 'inventory', 'label' => 'Auto-create Batch', 'description' => 'Automatically create batch numbers on stock addition'],
            ['key' => 'voucher_prefix_addition', 'value' => 'ADD', 'type' => 'string', 'group' => 'vouchers', 'label' => 'Addition Prefix', 'description' => 'Prefix for stock addition vouchers'],
            ['key' => 'voucher_prefix_issue', 'value' => 'ISS', 'type' => 'string', 'group' => 'vouchers', 'label' => 'Issue Prefix', 'description' => 'Prefix for stock issue vouchers'],
            ['key' => 'voucher_prefix_return', 'value' => 'RET', 'type' => 'string', 'group' => 'vouchers', 'label' => 'Return Prefix', 'description' => 'Prefix for stock return vouchers'],
            ['key' => 'voucher_prefix_transfer', 'value' => 'TRF', 'type' => 'string', 'group' => 'vouchers', 'label' => 'Transfer Prefix', 'description' => 'Prefix for transfer vouchers'],
            ['key' => 'voucher_prefix_adjustment', 'value' => 'ADJ', 'type' => 'string', 'group' => 'vouchers', 'label' => 'Adjustment Prefix', 'description' => 'Prefix for adjustment vouchers'],
            ['key' => 'auto_post_vouchers', 'value' => false, 'type' => 'boolean', 'group' => 'vouchers', 'label' => 'Auto-post Vouchers', 'description' => 'Automatically post vouchers on creation'],
            ['key' => 'expiry_alert_days', 'value' => 30, 'type' => 'number', 'group' => 'alerts', 'label' => 'Expiry Alert Days', 'description' => 'Days ahead to show expiry alerts'],
            ['key' => 'low_stock_alert', 'value' => true, 'type' => 'boolean', 'group' => 'alerts', 'label' => 'Low Stock Alerts', 'description' => 'Enable low stock notifications'],
            ['key' => 'report_default_period', 'value' => 'month', 'type' => 'select', 'group' => 'reports', 'label' => 'Default Report Period', 'description' => 'Default period for reports', 'options' => ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year']],
            ['key' => 'print_header_text', 'value' => 'Multi-Kitchen Inventory Management System', 'type' => 'text', 'group' => 'print', 'label' => 'Print Header Text', 'description' => 'Text displayed in print headers'],
            ['key' => 'print_footer_text', 'value' => 'Confidential - For Internal Use Only', 'type' => 'text', 'group' => 'print', 'label' => 'Print Footer Text', 'description' => 'Text displayed in print footers'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['is_public' => in_array($setting['key'], ['company_name', 'currency', 'currency_symbol', 'date_format', 'language', 'rtl_support'])])
            );
        }

        // Create Warehouses
        $mainWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Central Warehouse',
                'notes' => 'Central warehouse for all inventory storage',
                'address' => '123 Industrial Zone, Main City',
                'manager_name' => 'John Warehouse Manager',
                'phone' => '+1-555-0100',
                'email' => 'warehouse@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $coldWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-COLD'],
            [
                'name' => 'Cold Storage Warehouse',
                'notes' => 'Temperature-controlled warehouse for perishables',
                'address' => '456 Cold Chain Blvd, Main City',
                'manager_name' => 'Sarah Cold Storage',
                'phone' => '+1-555-0101',
                'email' => 'cold@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $dryWarehouse = Warehouse::firstOrCreate(
            ['code' => 'WH-DRY'],
            [
                'name' => 'Dry Goods Warehouse',
                'notes' => 'Dry storage for non-perishable items',
                'address' => '789 Dry Goods Ave, Main City',
                'manager_name' => 'Mike Dry Goods',
                'phone' => '+1-555-0102',
                'email' => 'dry@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Create Kitchens
        $kitchen1 = Kitchen::firstOrCreate(
            ['code' => 'KIT-001'],
            [
                'name' => 'Main Kitchen',
                'notes' => 'Primary production kitchen',
                'address' => '100 Restaurant Row, Main City',
                'manager_name' => 'Chef Gordon',
                'phone' => '+1-555-0200',
                'email' => 'mainkitchen@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $kitchen2 = Kitchen::firstOrCreate(
            ['code' => 'KIT-002'],
            [
                'name' => 'Banquet Kitchen',
                'notes' => 'Kitchen for banquet and event catering',
                'address' => '200 Event Center Dr, Main City',
                'manager_name' => 'Chef Marie',
                'phone' => '+1-555-0201',
                'email' => 'banquet@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $kitchen3 = Kitchen::firstOrCreate(
            ['code' => 'KIT-003'],
            [
                'name' => 'Pastry Kitchen',
                'notes' => 'Dedicated pastry and dessert kitchen',
                'address' => '300 Sweet Street, Main City',
                'manager_name' => 'Chef Pierre',
                'phone' => '+1-555-0202',
                'email' => 'pastry@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $kitchen4 = Kitchen::firstOrCreate(
            ['code' => 'KIT-004'],
            [
                'name' => 'Prep Kitchen',
                'notes' => 'Food preparation and mise en place kitchen',
                'address' => '400 Prep Lane, Main City',
                'manager_name' => 'Chef Antonio',
                'phone' => '+1-555-0203',
                'email' => 'prep@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $kitchen5 = Kitchen::firstOrCreate(
            ['code' => 'KIT-005'],
            [
                'name' => 'Satellite Kitchen',
                'notes' => 'Remote satellite kitchen for off-site events',
                'address' => '500 Satellite Blvd, Suburb City',
                'manager_name' => 'Chef Lisa',
                'phone' => '+1-555-0204',
                'email' => 'satellite@inventory.com',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Create Storage Locations
        // Main Warehouse locations
        $mainReceiving = StorageLocation::firstOrCreate(
            ['warehouse_id' => $mainWarehouse->id, 'code' => 'RCV'],
            [
                'name' => 'Receiving Dock',
                'type' => 'other',
                'description' => 'Initial receiving and inspection area',
                'capacity' => 500,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $mainDryStorage = StorageLocation::firstOrCreate(
            ['warehouse_id' => $mainWarehouse->id, 'code' => 'DRY'],
            [
                'name' => 'Dry Storage Aisle 1-5',
                'type' => 'dry',
                'description' => 'Main dry goods storage',
                'capacity' => 2000,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $mainShelf = StorageLocation::firstOrCreate(
            ['warehouse_id' => $mainWarehouse->id, 'code' => 'SHF'],
            [
                'name' => 'Shelving Units',
                'type' => 'shelf',
                'description' => 'Organized shelving for small items',
                'capacity' => 1000,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Cold Warehouse locations
        $coldRoom = StorageLocation::firstOrCreate(
            ['warehouse_id' => $coldWarehouse->id, 'code' => 'CLD'],
            [
                'name' => 'Cold Room (2-4°C)',
                'type' => 'cold',
                'description' => 'Refrigerated storage for vegetables, dairy',
                'capacity' => 800,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $freezer = StorageLocation::firstOrCreate(
            ['warehouse_id' => $coldWarehouse->id, 'code' => 'FRZ'],
            [
                'name' => 'Freezer (-18°C)',
                'type' => 'freezer',
                'description' => 'Frozen storage for meat, ice cream, frozen goods',
                'capacity' => 600,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Dry Warehouse locations
        $dryBulk = StorageLocation::firstOrCreate(
            ['warehouse_id' => $dryWarehouse->id, 'code' => 'BLK'],
            [
                'name' => 'Bulk Storage',
                'type' => 'dry',
                'description' => 'Bulk ingredients (flour, rice, oil)',
                'capacity' => 3000,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Kitchen locations
        $kitchen1Storage = StorageLocation::firstOrCreate(
            ['warehouse_id' => $mainWarehouse->id, 'code' => 'K1S'],
            [
                'name' => 'Main Kitchen Storage',
                'type' => 'other',
                'description' => 'Dedicated storage for Main Kitchen',
                'capacity' => 200,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $kitchen2Storage = StorageLocation::firstOrCreate(
            ['warehouse_id' => $mainWarehouse->id, 'code' => 'K2S'],
            [
                'name' => 'Banquet Kitchen Storage',
                'type' => 'other',
                'description' => 'Dedicated storage for Banquet Kitchen',
                'capacity' => 150,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Create Unit Categories
        $weightCategory = UnitCategory::firstOrCreate(
            ['code' => 'WGT'],
            [
                'name' => 'Weight',
                'description' => 'Weight-based measurements',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $volumeCategory = UnitCategory::firstOrCreate(
            ['code' => 'VOL'],
            [
                'name' => 'Volume',
                'description' => 'Volume-based measurements',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $packagingCategory = UnitCategory::firstOrCreate(
            ['code' => 'PKG'],
            [
                'name' => 'Packaging',
                'description' => 'Packaging and count-based units',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $lengthCategory = UnitCategory::firstOrCreate(
            ['code' => 'LEN'],
            [
                'name' => 'Length',
                'description' => 'Length-based measurements',
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Create Units
        // Weight units
        $kg = Unit::firstOrCreate(
            ['unit_category_id' => $weightCategory->id, 'name' => 'Kilogram'],
            [
                'name' => 'Kilogram',
                'symbol' => 'kg',
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'sort_order' => 1,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $gram = Unit::firstOrCreate(
            ['unit_category_id' => $weightCategory->id, 'name' => 'Gram'],
            [
                'name' => 'Gram',
                'symbol' => 'g',
                'conversion_factor' => 0.001,
                'is_base_unit' => false,
                'sort_order' => 2,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $pound = Unit::firstOrCreate(
            ['unit_category_id' => $weightCategory->id, 'name' => 'Pound'],
            [
                'name' => 'Pound',
                'symbol' => 'lb',
                'conversion_factor' => 0.453592,
                'is_base_unit' => false,
                'sort_order' => 3,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $ounce = Unit::firstOrCreate(
            ['unit_category_id' => $weightCategory->id, 'name' => 'Ounce'],
            [
                'name' => 'Ounce',
                'symbol' => 'oz',
                'conversion_factor' => 0.0283495,
                'is_base_unit' => false,
                'sort_order' => 4,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Volume units
        $liter = Unit::firstOrCreate(
            ['unit_category_id' => $volumeCategory->id, 'name' => 'Liter'],
            [
                'name' => 'Liter',
                'symbol' => 'L',
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'sort_order' => 1,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $ml = Unit::firstOrCreate(
            ['unit_category_id' => $volumeCategory->id, 'name' => 'Milliliter'],
            [
                'name' => 'Milliliter',
                'symbol' => 'ml',
                'conversion_factor' => 0.001,
                'is_base_unit' => false,
                'sort_order' => 2,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $gallon = Unit::firstOrCreate(
            ['unit_category_id' => $volumeCategory->id, 'name' => 'Gallon'],
            [
                'name' => 'Gallon',
                'symbol' => 'gal',
                'conversion_factor' => 3.78541,
                'is_base_unit' => false,
                'sort_order' => 3,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Packaging units
        $piece = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Piece'],
            [
                'name' => 'Piece',
                'symbol' => 'pc',
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'sort_order' => 1,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $box = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Box'],
            [
                'name' => 'Box',
                'symbol' => 'box',
                'conversion_factor' => 1,
                'is_base_unit' => false,
                'sort_order' => 2,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $carton = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Carton'],
            [
                'name' => 'Carton',
                'symbol' => 'ctn',
                'conversion_factor' => 12,
                'is_base_unit' => false,
                'sort_order' => 3,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $packet = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Packet'],
            [
                'name' => 'Packet',
                'symbol' => 'pkt',
                'conversion_factor' => 1,
                'is_base_unit' => false,
                'sort_order' => 4,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $bag = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Bag'],
            [
                'name' => 'Bag',
                'symbol' => 'bag',
                'conversion_factor' => 1,
                'is_base_unit' => false,
                'sort_order' => 5,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $bottle = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Bottle'],
            [
                'name' => 'Bottle',
                'symbol' => 'btl',
                'conversion_factor' => 1,
                'is_base_unit' => false,
                'sort_order' => 6,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $can = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Can'],
            [
                'name' => 'Can',
                'symbol' => 'can',
                'conversion_factor' => 1,
                'is_base_unit' => false,
                'sort_order' => 7,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $dozen = Unit::firstOrCreate(
            ['unit_category_id' => $packagingCategory->id, 'name' => 'Dozen'],
            [
                'name' => 'Dozen',
                'symbol' => 'doz',
                'conversion_factor' => 12,
                'is_base_unit' => false,
                'sort_order' => 8,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Length units
        $meter = Unit::firstOrCreate(
            ['unit_category_id' => $lengthCategory->id, 'name' => 'Meter'],
            [
                'name' => 'Meter',
                'symbol' => 'm',
                'conversion_factor' => 1,
                'is_base_unit' => true,
                'sort_order' => 1,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        $cm = Unit::firstOrCreate(
            ['unit_category_id' => $lengthCategory->id, 'name' => 'Centimeter'],
            [
                'name' => 'Centimeter',
                'symbol' => 'cm',
                'conversion_factor' => 0.01,
                'is_base_unit' => false,
                'sort_order' => 2,
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        // Create Categories
        $vegetables = Category::firstOrCreate(
            ['code' => 'VEG'],
            [
                'name' => 'Vegetables',
                'description' => 'Fresh vegetables and produce',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $admin->id,
            ]
        );

        $fruits = Category::firstOrCreate(
            ['code' => 'FRU'],
            [
                'name' => 'Fruits',
                'description' => 'Fresh fruits',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $admin->id,
            ]
        );

        $meat = Category::firstOrCreate(
            ['code' => 'MEA'],
            [
                'name' => 'Meat & Poultry',
                'description' => 'Fresh and frozen meat products',
                'status' => 'active',
                'sort_order' => 3,
                'created_by' => $admin->id,
            ]
        );

        $seafood = Category::firstOrCreate(
            ['code' => 'SEA'],
            [
                'name' => 'Seafood',
                'description' => 'Fresh and frozen seafood',
                'status' => 'active',
                'sort_order' => 4,
                'created_by' => $admin->id,
            ]
        );

        $dairy = Category::firstOrCreate(
            ['code' => 'DAI'],
            [
                'name' => 'Dairy & Eggs',
                'description' => 'Milk, cheese, butter, eggs',
                'status' => 'active',
                'sort_order' => 5,
                'created_by' => $admin->id,
            ]
        );

        $dryGoods = Category::firstOrCreate(
            ['code' => 'DRY'],
            [
                'name' => 'Dry Goods',
                'description' => 'Flour, rice, pasta, canned goods',
                'status' => 'active',
                'sort_order' => 6,
                'created_by' => $admin->id,
            ]
        );

        $beverages = Category::firstOrCreate(
            ['code' => 'BEV'],
            [
                'name' => 'Beverages',
                'description' => 'Drinks, juices, water',
                'status' => 'active',
                'sort_order' => 7,
                'created_by' => $admin->id,
            ]
        );

        $bakery = Category::firstOrCreate(
            ['code' => 'BAK'],
            [
                'name' => 'Bakery',
                'description' => 'Bread, pastries, desserts',
                'status' => 'active',
                'sort_order' => 8,
                'created_by' => $admin->id,
            ]
        );

        $spices = Category::firstOrCreate(
            ['code' => 'SPI'],
            [
                'name' => 'Spices & Condiments',
                'description' => 'Herbs, spices, sauces, oils',
                'status' => 'active',
                'sort_order' => 9,
                'created_by' => $admin->id,
            ]
        );

        $cleaning = Category::firstOrCreate(
            ['code' => 'CLN'],
            [
                'name' => 'Cleaning Supplies',
                'description' => 'Cleaning chemicals, sanitizers',
                'status' => 'active',
                'sort_order' => 10,
                'created_by' => $admin->id,
            ]
        );

        // Subcategories
        $leafyGreens = Category::firstOrCreate(
            ['code' => 'LEA'],
            [
                'parent_id' => $vegetables->id,
                'name' => 'Leafy Greens',
                'description' => 'Lettuce, spinach, kale, etc.',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $admin->id,
            ]
        );

        $rootVegetables = Category::firstOrCreate(
            ['code' => 'ROO'],
            [
                'parent_id' => $vegetables->id,
                'name' => 'Root Vegetables',
                'description' => 'Carrots, potatoes, onions, etc.',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $admin->id,
            ]
        );

        $beef = Category::firstOrCreate(
            ['code' => 'BEF'],
            [
                'parent_id' => $meat->id,
                'name' => 'Beef',
                'description' => 'Beef cuts and ground beef',
                'status' => 'active',
                'sort_order' => 1,
                'created_by' => $admin->id,
            ]
        );

        $chicken = Category::firstOrCreate(
            ['code' => 'CHK'],
            [
                'parent_id' => $meat->id,
                'name' => 'Chicken',
                'description' => 'Chicken pieces and whole',
                'status' => 'active',
                'sort_order' => 2,
                'created_by' => $admin->id,
            ]
        );

        // Create Items
        $items = [
            [
                'name' => 'Tomatoes',
                'sku' => 'TOM-001',
                'barcode' => 'ITEM-00000001',
                'category_id' => $vegetables->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $carton->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 2.50,
                'cost_price' => 3.00,
                'min_level' => 10,
                'max_level' => 100,
                'reorder_point' => 20,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Fresh red tomatoes, grade A',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Lettuce Iceberg',
                'sku' => 'LET-001',
                'barcode' => 'ITEM-00000002',
                'category_id' => $vegetables->id,
                'subcategory_id' => $leafyGreens->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $carton->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 1.80,
                'cost_price' => 2.20,
                'min_level' => 5,
                'max_level' => 50,
                'reorder_point' => 10,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Crisp iceberg lettuce heads',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Carrots',
                'sku' => 'CAR-001',
                'barcode' => 'ITEM-00000003',
                'category_id' => $vegetables->id,
                'subcategory_id' => $rootVegetables->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 1.20,
                'cost_price' => 1.50,
                'min_level' => 15,
                'max_level' => 100,
                'reorder_point' => 25,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Fresh carrots, 1kg bags',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Chicken Breast',
                'sku' => 'CHK-001',
                'barcode' => 'ITEM-00000004',
                'category_id' => $meat->id,
                'subcategory_id' => $chicken->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $box->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 8.50,
                'cost_price' => 10.00,
                'min_level' => 10,
                'max_level' => 50,
                'reorder_point' => 15,
                'default_storage_location_id' => $freezer->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Boneless skinless chicken breast',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Ground Beef',
                'sku' => 'BEF-001',
                'barcode' => 'ITEM-00000005',
                'category_id' => $meat->id,
                'subcategory_id' => $beef->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $box->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 12.00,
                'cost_price' => 14.50,
                'min_level' => 5,
                'max_level' => 30,
                'reorder_point' => 10,
                'default_storage_location_id' => $freezer->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => '80/20 ground beef',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Milk Whole',
                'sku' => 'MIL-001',
                'barcode' => 'ITEM-00000006',
                'category_id' => $dairy->id,
                'subcategory_id' => null,
                'unit_category_id' => $volumeCategory->id,
                'base_unit_id' => $liter->id,
                'default_purchase_unit_id' => $bottle->id,
                'default_issue_unit_id' => $liter->id,
                'purchase_price' => 1.50,
                'cost_price' => 1.80,
                'min_level' => 20,
                'max_level' => 100,
                'reorder_point' => 30,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Whole milk 1L bottles',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Eggs Large',
                'sku' => 'EGG-001',
                'barcode' => 'ITEM-00000007',
                'category_id' => $dairy->id,
                'subcategory_id' => null,
                'unit_category_id' => $packagingCategory->id,
                'base_unit_id' => $piece->id,
                'default_purchase_unit_id' => $dozen->id,
                'default_issue_unit_id' => $piece->id,
                'purchase_price' => 0.30,
                'cost_price' => 0.40,
                'min_level' => 60,
                'max_level' => 300,
                'reorder_point' => 120,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Large grade A eggs',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Rice Basmati',
                'sku' => 'RIC-001',
                'barcode' => 'ITEM-00000008',
                'category_id' => $dryGoods->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 3.50,
                'cost_price' => 4.00,
                'min_level' => 25,
                'max_level' => 200,
                'reorder_point' => 50,
                'default_storage_location_id' => $dryBulk->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Premium basmati rice 5kg bags',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Olive Oil Extra Virgin',
                'sku' => 'OIL-001',
                'barcode' => 'ITEM-00000009',
                'category_id' => $spices->id,
                'subcategory_id' => null,
                'unit_category_id' => $volumeCategory->id,
                'base_unit_id' => $liter->id,
                'default_purchase_unit_id' => $bottle->id,
                'default_issue_unit_id' => $liter->id,
                'purchase_price' => 15.00,
                'cost_price' => 18.00,
                'min_level' => 5,
                'max_level' => 30,
                'reorder_point' => 10,
                'default_storage_location_id' => $mainDryStorage->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Extra virgin olive oil 1L bottles',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Flour All Purpose',
                'sku' => 'FLR-001',
                'barcode' => 'ITEM-00000010',
                'category_id' => $dryGoods->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 1.80,
                'cost_price' => 2.20,
                'min_level' => 20,
                'max_level' => 150,
                'reorder_point' => 40,
                'default_storage_location_id' => $dryBulk->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'All-purpose flour 10kg bags',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Sugar White',
                'sku' => 'SUG-001',
                'barcode' => 'ITEM-00000011',
                'category_id' => $dryGoods->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 1.20,
                'cost_price' => 1.50,
                'min_level' => 15,
                'max_level' => 100,
                'reorder_point' => 30,
                'default_storage_location_id' => $dryBulk->id,
                'track_expiry' => false,
                'is_active' => true,
                'description' => 'White granulated sugar 10kg bags',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Salt Table',
                'sku' => 'SAL-001',
                'barcode' => 'ITEM-00000012',
                'category_id' => $spices->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 0.80,
                'cost_price' => 1.00,
                'min_level' => 10,
                'max_level' => 50,
                'reorder_point' => 20,
                'default_storage_location_id' => $mainDryStorage->id,
                'track_expiry' => false,
                'is_active' => true,
                'description' => 'Iodized table salt 1kg',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Black Pepper',
                'sku' => 'PEP-001',
                'barcode' => 'ITEM-00000013',
                'category_id' => $spices->id,
                'subcategory_id' => null,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $gram->id,
                'default_purchase_unit_id' => $packet->id,
                'default_issue_unit_id' => $gram->id,
                'purchase_price' => 0.05,
                'cost_price' => 0.08,
                'min_level' => 500,
                'max_level' => 2000,
                'reorder_point' => 1000,
                'default_storage_location_id' => $mainDryStorage->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Ground black pepper 50g packets',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Onions Yellow',
                'sku' => 'ONI-001',
                'barcode' => 'ITEM-00000014',
                'category_id' => $vegetables->id,
                'subcategory_id' => $rootVegetables->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $bag->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 1.00,
                'cost_price' => 1.30,
                'min_level' => 20,
                'max_level' => 100,
                'reorder_point' => 30,
                'default_storage_location_id' => $mainDryStorage->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Yellow onions 5kg bags',
                'created_by' => $admin->id,
            ],
            [
                'name' => 'Garlic Fresh',
                'sku' => 'GAR-001',
                'barcode' => 'ITEM-00000015',
                'category_id' => $vegetables->id,
                'subcategory_id' => $rootVegetables->id,
                'unit_category_id' => $weightCategory->id,
                'base_unit_id' => $kg->id,
                'default_purchase_unit_id' => $packet->id,
                'default_issue_unit_id' => $kg->id,
                'purchase_price' => 6.00,
                'cost_price' => 7.50,
                'min_level' => 3,
                'max_level' => 20,
                'reorder_point' => 5,
                'default_storage_location_id' => $coldRoom->id,
                'track_expiry' => true,
                'is_active' => true,
                'description' => 'Fresh garlic heads',
                'created_by' => $admin->id,
            ],
        ];

        $createdItems = [];
        foreach ($items as $itemData) {
            $item = Item::firstOrCreate(
                ['sku' => $itemData['sku']],
                $itemData
            );
            $createdItems[$itemData['sku']] = $item;
        }

        // Create Batches with stock
        $batchData = [
            // Tomatoes
            ['item' => 'TOM-001', 'batch_number' => 'TOM-2024-001', 'quantity' => 50, 'location' => $coldRoom, 'expiry' => now()->addDays(5), 'cost' => 3.00],
            ['item' => 'TOM-001', 'batch_number' => 'TOM-2024-002', 'quantity' => 30, 'location' => $coldRoom, 'expiry' => now()->addDays(10), 'cost' => 3.00],
            ['item' => 'TOM-001', 'batch_number' => 'TOM-2024-003', 'quantity' => 20, 'location' => $coldRoom, 'expiry' => now()->addDays(15), 'cost' => 3.00],

            // Lettuce
            ['item' => 'LET-001', 'batch_number' => 'LET-2024-001', 'quantity' => 25, 'location' => $coldRoom, 'expiry' => now()->addDays(3), 'cost' => 2.20],
            ['item' => 'LET-001', 'batch_number' => 'LET-2024-002', 'quantity' => 15, 'location' => $coldRoom, 'expiry' => now()->addDays(7), 'cost' => 2.20],

            // Carrots
            ['item' => 'CAR-001', 'batch_number' => 'CAR-2024-001', 'quantity' => 80, 'location' => $coldRoom, 'expiry' => now()->addDays(20), 'cost' => 1.50],
            ['item' => 'CAR-001', 'batch_number' => 'CAR-2024-002', 'quantity' => 40, 'location' => $coldRoom, 'expiry' => now()->addDays(30), 'cost' => 1.50],

            // Chicken Breast
            ['item' => 'CHK-001', 'batch_number' => 'CHK-2024-001', 'quantity' => 40, 'location' => $freezer, 'expiry' => now()->addDays(60), 'cost' => 10.00],
            ['item' => 'CHK-001', 'batch_number' => 'CHK-2024-002', 'quantity' => 20, 'location' => $freezer, 'expiry' => now()->addDays(90), 'cost' => 10.00],

            // Ground Beef
            ['item' => 'BEF-001', 'batch_number' => 'BEF-2024-001', 'quantity' => 15, 'location' => $freezer, 'expiry' => now()->addDays(30), 'cost' => 14.50],
            ['item' => 'BEF-001', 'batch_number' => 'BEF-2024-002', 'quantity' => 10, 'location' => $freezer, 'expiry' => now()->addDays(45), 'cost' => 14.50],

            // Milk
            ['item' => 'MIL-001', 'batch_number' => 'MIL-2024-001', 'quantity' => 60, 'location' => $coldRoom, 'expiry' => now()->addDays(7), 'cost' => 1.80],
            ['item' => 'MIL-001', 'batch_number' => 'MIL-2024-002', 'quantity' => 40, 'location' => $coldRoom, 'expiry' => now()->addDays(10), 'cost' => 1.80],

            // Eggs
            ['item' => 'EGG-001', 'batch_number' => 'EGG-2024-001', 'quantity' => 200, 'location' => $coldRoom, 'expiry' => now()->addDays(14), 'cost' => 0.40],
            ['item' => 'EGG-001', 'batch_number' => 'EGG-2024-002', 'quantity' => 100, 'location' => $coldRoom, 'expiry' => now()->addDays(21), 'cost' => 0.40],

            // Rice
            ['item' => 'RIC-001', 'batch_number' => 'RIC-2024-001', 'quantity' => 100, 'location' => $dryBulk, 'expiry' => now()->addDays(365), 'cost' => 4.00],

            // Olive Oil
            ['item' => 'OIL-001', 'batch_number' => 'OIL-2024-001', 'quantity' => 20, 'location' => $mainDryStorage, 'expiry' => now()->addDays(180), 'cost' => 18.00],

            // Flour
            ['item' => 'FLR-001', 'batch_number' => 'FLR-2024-001', 'quantity' => 80, 'location' => $dryBulk, 'expiry' => now()->addDays(180), 'cost' => 2.20],

            // Sugar
            ['item' => 'SUG-001', 'batch_number' => 'SUG-2024-001', 'quantity' => 50, 'location' => $dryBulk, 'expiry' => null, 'cost' => 1.50],

            // Salt
            ['item' => 'SAL-001', 'batch_number' => 'SAL-2024-001', 'quantity' => 30, 'location' => $mainDryStorage, 'expiry' => null, 'cost' => 1.00],

            // Pepper
            ['item' => 'PEP-001', 'batch_number' => 'PEP-2024-001', 'quantity' => 1500, 'location' => $mainDryStorage, 'expiry' => now()->addDays(365), 'cost' => 0.08],

            // Onions
            ['item' => 'ONI-001', 'batch_number' => 'ONI-2024-001', 'quantity' => 60, 'location' => $mainDryStorage, 'expiry' => now()->addDays(30), 'cost' => 1.30],
            ['item' => 'ONI-001', 'batch_number' => 'ONI-2024-002', 'quantity' => 40, 'location' => $mainDryStorage, 'expiry' => now()->addDays(45), 'cost' => 1.30],

            // Garlic
            ['item' => 'GAR-001', 'batch_number' => 'GAR-2024-001', 'quantity' => 10, 'location' => $coldRoom, 'expiry' => now()->addDays(20), 'cost' => 7.50],
        ];

        foreach ($batchData as $batch) {
            $item = $createdItems[$batch['item']];
            ItemBatch::firstOrCreate(
                ['item_id' => $item->id, 'batch_number' => $batch['batch_number']],
                [
                    'item_id' => $item->id,
                    'batch_number' => $batch['batch_number'],
                    'quantity_received' => $batch['quantity'],
                    'quantity_remaining' => $batch['quantity'],
                    'expiry_date' => $batch['expiry'],
                    'storage_location_id' => $batch['location']->id,
                    'unit_cost' => $batch['cost'],
                    'status' => 'active',
                    'created_by' => $admin->id,
                ]
            );
        }

        // Create some stock vouchers for history
        $voucherTypes = ['addition', 'issue', 'return', 'transfer', 'adjustment'];

        // Create a few addition vouchers
        for ($i = 1; $i <= 5; $i++) {
            $voucher = StockVoucher::create([
                'voucher_type' => 'addition',
                'voucher_number' => 'ADD-'.now()->format('Ymd').'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'transaction_date' => now()->subDays(rand(1, 30)),
                'warehouse_id' => $mainWarehouse->id,
                'to_storage_location_id' => $mainDryStorage->id,
                'notes' => "Stock addition #{$i}",
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(rand(1, 30)),
            ]);

            // Add 2-3 items per voucher
            $itemKeys = array_rand($createdItems, rand(2, 3));
            foreach ((array) $itemKeys as $key) {
                $item = $createdItems[$key];
                $qty = rand(10, 50);
                StockVoucher::find($voucher->id)->items()->create([
                    'item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_id' => $item->base_unit_id,
                    'conversion_factor' => 1,
                    'unit_price' => $item->cost_price,
                    'to_storage_location_id' => $item->default_storage_location_id,
                    'created_by' => $admin->id,
                ]);

                $voucherItem = StockVoucher::find($voucher->id)->items()->latest()->first();
                StockMovement::create([
                    'voucher_id' => $voucher->id,
                    'voucher_item_id' => $voucherItem->id,
                    'item_id' => $item->id,
                    'movement_type' => 'in',
                    'direction' => 'in',
                    'quantity' => $qty,
                    'unit_id' => $item->base_unit_id,
                    'conversion_factor' => 1,
                    'quantity_base' => $qty,
                    'unit_price' => $item->cost_price,
                    'total_cost' => $qty * $item->cost_price,
                    'storage_location_id' => $item->default_storage_location_id,
                    'running_balance_base' => $item->current_stock,
                    'performed_by' => $admin->id,
                    'movement_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        // Create a few issue vouchers
        for ($i = 1; $i <= 3; $i++) {
            $voucher = StockVoucher::create([
                'voucher_type' => 'issue',
                'voucher_number' => 'ISS-'.now()->format('Ymd').'-'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'transaction_date' => now()->subDays(rand(1, 30)),
                'warehouse_id' => $mainWarehouse->id,
                'kitchen_id' => $kitchen1->id,
                'from_storage_location_id' => $mainDryStorage->id,
                'notes' => "Issue to kitchen #{$i}",
                'status' => 'approved',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(rand(1, 30)),
            ]);

            $itemKeys = array_rand($createdItems, rand(2, 4));
            foreach ((array) $itemKeys as $key) {
                $item = $createdItems[$key];
                $qty = rand(5, 20);
                StockVoucher::find($voucher->id)->items()->create([
                    'item_id' => $item->id,
                    'quantity' => $qty,
                    'unit_id' => $item->base_unit_id,
                    'conversion_factor' => 1,
                    'unit_price' => $item->cost_price,
                    'from_storage_location_id' => $item->default_storage_location_id,
                    'created_by' => $admin->id,
                ]);

                $voucherItem = StockVoucher::find($voucher->id)->items()->latest()->first();
                StockMovement::create([
                    'voucher_id' => $voucher->id,
                    'voucher_item_id' => $voucherItem->id,
                    'item_id' => $item->id,
                    'movement_type' => 'out',
                    'direction' => 'out',
                    'quantity' => $qty,
                    'unit_id' => $item->base_unit_id,
                    'conversion_factor' => 1,
                    'quantity_base' => $qty,
                    'unit_price' => $item->cost_price,
                    'total_cost' => $qty * $item->cost_price,
                    'storage_location_id' => $item->default_storage_location_id,
                    'running_balance_base' => max(0, $item->current_stock - $qty),
                    'performed_by' => $admin->id,
                    'movement_date' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}
