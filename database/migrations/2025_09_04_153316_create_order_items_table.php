<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained('menu_items')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price', 10, 2); // snapshot at add time
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            // optional: prevent duplicates per menu_item per order
            // $table->unique(['order_id','menu_item_id']);
        });
        // Optional: DB-level check — quantity > 0
        DB::statement("ALTER TABLE order_items ADD CONSTRAINT chk_quantity_positive CHECK (quantity > 0)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
