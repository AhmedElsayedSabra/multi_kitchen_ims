<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Kitchen;
use App\Models\StockVoucher;
use App\Models\StockVoucherItem;
use App\Models\StorageLocation;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireCrudModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_create_and_edit_modals_render_and_save(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $item = $this->createItemPrerequisites($user);
        $otherUnitCategory = UnitCategory::create([
            'name' => 'Alternate Item Units',
            'code' => 'ALT-ITEM-UNIT',
            'status' => 'active',
        ]);
        $otherBaseUnit = Unit::create([
            'unit_category_id' => $otherUnitCategory->id,
            'name' => 'Alternate Base Unit',
            'symbol' => 'abu',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'status' => 'active',
        ]);
        $warehouse = Warehouse::create([
            'name' => 'Default Item Warehouse',
            'code' => 'DEFAULT-ITEM-WH',
            'status' => 'active',
        ]);
        $storageLocation = StorageLocation::create([
            'warehouse_id' => $warehouse->id,
            'name' => 'Default Item Location',
            'code' => 'DEFAULT-ITEM-LOC',
            'status' => 'active',
        ]);
        Storage::fake('public');

        $component = Livewire::test('inventory::items.index')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSee('Add New Item')
            ->assertSee('<select', false)
            ->assertSee('-- Select Category --')
            ->assertSee('Test Item Category')
            ->assertSee('value="'.$item->category_id.'"', false)
            ->assertSee('background-color: #ffffff; color: #0f172a;', false)
            ->assertSet('category_id', $item->category_id)
            ->assertSet('unit_category_id', $item->unit_category_id)
            ->assertSet('base_unit_id', $item->base_unit_id)
            ->assertSet('default_purchase_unit_id', $item->base_unit_id)
            ->assertSet('default_issue_unit_id', $item->base_unit_id)
            ->assertSet('default_storage_location_id', $storageLocation->id)
            ->set('name', 'Generated Modal Item')
            ->assertSet('sku', 'GENERATED-MODAL-ITEM')
            ->set('name', 'Modal Test Item')
            ->assertSet('sku', 'MODAL-TEST-ITEM')
            ->set('sku', 'MODAL-ITEM')
            ->set('name', 'Name Does Not Override Manual SKU')
            ->assertSet('sku', 'MODAL-ITEM')
            ->set('name', 'Modal Test Item')
            ->set('category_id', $item->category_id)
            ->set('unit_category_id', $item->unit_category_id)
            ->assertSet('base_unit_id', $item->base_unit_id)
            ->assertSet('default_purchase_unit_id', $item->base_unit_id)
            ->assertSet('default_issue_unit_id', $item->base_unit_id)
            ->set('unit_category_id', $otherUnitCategory->id)
            ->assertSet('base_unit_id', $otherBaseUnit->id)
            ->assertSet('default_purchase_unit_id', $otherBaseUnit->id)
            ->assertSet('default_issue_unit_id', $otherBaseUnit->id)
            ->set('base_unit_id', $otherBaseUnit->id)
            ->set('image', UploadedFile::fake()->createWithContent(
                'item-photo.jfif',
                file_get_contents(base_path('vendor/livewire/livewire/src/Features/SupportFileUploads/browser_test_image_big.jpg')),
            ))
            ->set('purchase_price', 10)
            ->set('cost_price', 8)
            ->set('min_level', 1)
            ->set('reorder_point', 2)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $createdItem = Item::where('sku', 'MODAL-ITEM')->firstOrFail();
        $this->assertDatabaseHas('items', [
            'id' => $createdItem->id,
            'name' => 'Modal Test Item',
            'sku' => 'MODAL-ITEM',
            'unit_category_id' => $otherUnitCategory->id,
            'base_unit_id' => $otherBaseUnit->id,
        ]);
        Storage::disk('public')->assertExists($createdItem->image);

        $component->call('openEditModal', $createdItem)
            ->assertSet('showEditModal', true)
            ->assertSee('Edit Item')
            ->set('name', 'Updated Modal Item')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('items', [
            'id' => $createdItem->id,
            'name' => 'Updated Modal Item',
        ]);
    }

    public function test_stock_voucher_create_and_edit_modals_render_and_save(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $item = $this->createItemPrerequisites($user);
        $purchaseUnit = Unit::create([
            'unit_category_id' => $item->unit_category_id,
            'name' => 'Voucher Purchase Unit',
            'symbol' => 'vpu',
            'is_base_unit' => false,
            'conversion_factor' => 12,
            'status' => 'active',
        ]);
        $item->update([
            'default_purchase_unit_id' => $purchaseUnit->id,
            'purchase_price' => 12,
            'cost_price' => 8,
        ]);
        $item->refresh();

        $component = Livewire::test('inventory::stock-vouchers.index')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSee('Create Addition Voucher')
            ->assertSee('<select', false)
            ->assertSee('Stock Addition')
            ->assertSee('Test Item')
            ->call('addItemRow')
            ->assertCount('items', 2)
            ->call('removeItemRow', 1)
            ->set('voucher_date', '2026-11-11')
            ->set('reference_no', 'MODAL-VOUCHER')
            ->set('items.0.item_id', $item->id)
            ->assertSet('items.0.unit_id', $purchaseUnit->id)
            ->assertSet('items.0.cost_price', 12)
            ->set('items.0.quantity', 5)
            ->set('items.0.batch_number', 'MODAL-BATCH')
            ->set('items.0.expiry_date', '2027-11-11')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $voucher = StockVoucher::where('voucher_number', 'MODAL-VOUCHER')->firstOrFail();
        $this->assertDatabaseHas('stock_vouchers', [
            'id' => $voucher->id,
            'voucher_type' => 'addition',
        ]);
        $voucherItem = StockVoucherItem::where('voucher_id', $voucher->id)->firstOrFail();
        $this->assertDatabaseHas('stock_voucher_items', [
            'id' => $voucherItem->id,
            'conversion_factor' => 12,
            'quantity_base' => 60,
            'unit_price' => 12,
        ]);
        $batch = ItemBatch::findOrFail($voucherItem->batch_id);
        $this->assertSame('2027-11-11', $batch->expiry_date->format('Y-m-d'));

        $component->call('openEditModal', $voucher)
            ->assertSet('showEditModal', true)
            ->assertSee('Edit Addition Voucher')
            ->set('reference_no', 'MODAL-VOUCHER-UPDATED')
            ->set('items.0.quantity', 6)
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('stock_vouchers', [
            'id' => $voucher->id,
            'voucher_number' => 'MODAL-VOUCHER-UPDATED',
        ]);
        $this->assertDatabaseHas('stock_voucher_items', [
            'voucher_id' => $voucher->id,
            'quantity' => 6,
            'quantity_base' => 72,
        ]);
    }

    public function test_kitchen_create_and_edit_modals_render_and_save(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test('inventory::kitchens.index')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSee('Add Kitchen')
            ->assertSee('<select', false)
            ->set('name', 'Modal Test Kitchen')
            ->set('code', 'MODAL-KIT')
            ->set('description', 'Kitchen description')
            ->set('contact_person', 'Test Manager')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $kitchen = Kitchen::where('code', 'MODAL-KIT')->firstOrFail();
        $this->assertDatabaseHas('kitchens', [
            'id' => $kitchen->id,
            'manager_name' => 'Test Manager',
            'notes' => 'Kitchen description',
        ]);

        $component->call('openEditModal', $kitchen)
            ->assertSet('showEditModal', true)
            ->assertSee('Edit Kitchen')
            ->set('name', 'Updated Modal Kitchen')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('kitchens', [
            'id' => $kitchen->id,
            'name' => 'Updated Modal Kitchen',
        ]);
    }

    public function test_unit_create_and_edit_modals_render_and_save(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $unitCategory = UnitCategory::create([
            'name' => 'Modal Test Units',
            'code' => 'MODAL-UNIT',
            'status' => 'active',
        ]);

        $component = Livewire::test('inventory::units.index')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSee('Add Unit')
            ->assertSee('<select', false)
            ->set('unit_category_id', $unitCategory->id)
            ->set('name', 'Modal Test Unit')
            ->set('symbol', 'mtu')
            ->set('conversion_factor', 1)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $unit = Unit::where('name', 'Modal Test Unit')->firstOrFail();
        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'unit_category_id' => $unitCategory->id,
        ]);

        $component->call('openEditModal', $unit)
            ->assertSet('showEditModal', true)
            ->assertSee('Edit Unit')
            ->set('name', 'Updated Modal Unit')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Modal Unit',
        ]);
    }

    public function test_category_create_and_edit_modals_render_and_save(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test('inventory::categories.index')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true)
            ->assertSee('Add Category')
            ->assertSee('<select', false)
            ->set('name', 'Modal Test Category')
            ->set('code', 'MODAL-CAT')
            ->call('create')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $category = Category::where('code', 'MODAL-CAT')->firstOrFail();
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Modal Test Category',
        ]);

        $component->call('openEditModal', $category)
            ->assertSet('showEditModal', true)
            ->assertSee('Edit Category')
            ->set('name', 'Updated Modal Category')
            ->call('update')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Modal Category',
        ]);
    }

    public function test_other_crud_create_modals_render_native_dropdowns(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach ([
            'inventory::warehouses.index',
            'inventory::storage-locations.index',
            'inventory::unit-categories.index',
            'inventory::users.index',
        ] as $componentName) {
            Livewire::test($componentName)
                ->call('openCreateModal')
                ->assertSee('<select', false)
                ->assertSee('<option value=""', false);
        }
    }

    private function createItemPrerequisites(User $user): Item
    {
        $category = Category::create([
            'name' => 'Test Item Category',
            'code' => 'TEST-CAT',
            'status' => 'active',
        ]);
        $unitCategory = UnitCategory::create([
            'name' => 'Test Item Unit Category',
            'code' => 'TEST-UNIT-CAT',
            'status' => 'active',
        ]);
        $unit = Unit::create([
            'unit_category_id' => $unitCategory->id,
            'name' => 'Test Item Unit',
            'symbol' => 'tiu',
            'is_base_unit' => true,
            'conversion_factor' => 1,
            'status' => 'active',
        ]);

        return Item::create([
            'name' => 'Test Item',
            'sku' => 'TEST-ITEM',
            'category_id' => $category->id,
            'unit_category_id' => $unitCategory->id,
            'base_unit_id' => $unit->id,
            'created_by' => $user->id,
        ]);
    }
}
