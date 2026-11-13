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
        Schema::create('item_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number'); // Batch/Lot number
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->decimal('quantity_received', 15, 4)->default(0); // In base unit
            $table->decimal('quantity_remaining', 15, 4)->default(0); // In base unit
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->enum('status', ['active', 'consumed', 'expired', 'wasted', 'returned'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['item_id', 'batch_number']);
            $table->index(['item_id', 'expiry_date']);
            $table->index(['storage_location_id', 'expiry_date']);
            $table->index(['expiry_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_batches');
    }
};
