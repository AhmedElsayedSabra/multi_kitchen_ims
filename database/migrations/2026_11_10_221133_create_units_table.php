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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('name'); // Kilogram, Ton, Gram, Packet, Box/Carton, Tea Bag, Sack, Crate, Barrel, Piece/Unit
            $table->string('symbol'); // kg, t, g, pkt, ctn, tb, sk, cr, bbl, pc
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('conversion_factor', 15, 6)->default(1.000000); // relative to base unit
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Ensure only one base unit per category (handled in application logic)
            // SQLite doesn't support partial indexes, so we use a unique constraint on name per category
            $table->unique(['unit_category_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
