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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Supports English & Arabic
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('base_unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('default_purchase_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('default_issue_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('min_level', 15, 4)->default(0);
            $table->decimal('max_level', 15, 4)->default(0);
            $table->decimal('reorder_point', 15, 4)->default(0);
            $table->foreignId('default_storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->boolean('track_expiry')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'subcategory_id']);
            $table->index(['is_active', 'track_expiry']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
