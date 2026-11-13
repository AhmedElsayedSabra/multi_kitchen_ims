<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('stock_vouchers')->cascadeOnDelete();
            $table->foreignId('voucher_item_id')->constrained('stock_voucher_items')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('item_batches')->nullOnDelete();
            $table->enum('movement_type', [
                'in',      // Addition, Return to Warehouse
                'out',     // Issue, Transfer Out, Waste
                'transfer', // Internal transfer (both in and out recorded)
            ]);
            $table->enum('direction', ['in', 'out']); // For transfer: in to location, out from location
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('kitchen_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4); // In transaction unit
            $table->decimal('conversion_factor', 15, 6);
            $table->decimal('quantity_base', 15, 4); // In base unit
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->decimal('running_balance_base', 15, 4)->default(0); // Balance after this movement
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('movement_date')->useCurrent();
            $table->text('notes')->nullable();

            $table->index(['item_id', 'movement_date']);
            $table->index(['batch_id', 'movement_date']);
            $table->index(['storage_location_id', 'movement_date']);
            $table->index(['kitchen_id', 'movement_date']);
            $table->index(['movement_type', 'movement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
