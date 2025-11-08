<?php
// filepath: database/migrations/[timestamp]_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->foreignId('waiter_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->enum('type', ['dine-in', 'takeout', 'delivery'])->default('dine-in');
            $table->enum('status', ['pending', 'accepted', 'preparing', 'ready', 'served', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['normal', 'rush', 'urgent'])->default('normal');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'mobile', 'split'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['restaurant_id', 'status']);
            $table->index(['restaurant_id', 'placed_at']);
            $table->index(['table_id', 'status']);
            $table->index(['waiter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};