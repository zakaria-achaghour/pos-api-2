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
        Schema::table('orders', function (Blueprint $table) {
             $table->enum('status', ['open', 'paid', 'cancelled', 'refunded'])->default('open')->change();
            $table->enum('priority', ['normal', 'rush'])->default('normal');
            $table->foreignId('waiter_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->enum('payment_method', ['cash', 'card', 'mobile'])->nullable();
            $table->decimal('tax_amount', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['open', 'paid', 'cancelled'])->default('open')->change();
            $table->dropColumn(['priority', 'waiter_id', 'payment_method', 'tax_amount', 'discount_amount', 'notes']);
        });
    }
};
