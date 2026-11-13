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
        Schema::create('stock_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->enum('voucher_type', [
                'addition',      // Stock In / Supply
                'issue',         // Stock Out / Withdrawal to Kitchen
                'return',        // Return from Kitchen or to Supplier
                'transfer',      // Internal Transfer between locations/kitchens
                'adjustment',    // Waste / Audit Correction
            ]);
            $table->date('transaction_date');
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('to_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('kitchen_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('users')->nullOnDelete(); // for returns to supplier
            $table->foreignId('reference_voucher_id')->nullable()->constrained('stock_vouchers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['voucher_type', 'transaction_date']);
            $table->index(['status', 'transaction_date']);
            $table->index(['kitchen_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_vouchers');
    }
};
