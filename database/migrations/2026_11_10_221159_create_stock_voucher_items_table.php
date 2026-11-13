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
        Schema::create('stock_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('stock_vouchers')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('item_batches')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete(); // Unit used in transaction
            $table->decimal('quantity', 15, 4); // Quantity in transaction unit
            $table->decimal('conversion_factor', 15, 6); // Conversion factor at time of transaction
            $table->decimal('quantity_base', 15, 4); // Quantity in base unit (calculated)
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);
            $table->foreignId('from_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('to_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['voucher_id', 'item_id']);
            $table->index(['item_id', 'batch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_voucher_items');
    }
};
