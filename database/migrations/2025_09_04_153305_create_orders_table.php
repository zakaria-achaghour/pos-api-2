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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // who created
            $table->string('order_number'); // readable per tenant
            $table->enum('status', ['open','paid','cancelled'])->default('open');
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamp('placed_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id','order_number']); // per tenant
            $table->index(['restaurant_id','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
