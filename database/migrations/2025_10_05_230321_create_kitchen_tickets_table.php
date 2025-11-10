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
        Schema::create('kitchen_tickets', function (Blueprint $table) {
             $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number');
            $table->enum('priority', ['normal', 'rush', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'preparing', 'ready', 'served'])->default('pending');
            $table->foreignId('assigned_chef_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('cooking_station')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('bumped_at')->nullable(); // when passed to expediter/server
            $table->integer('preparation_time')->nullable(); // in minutes
            $table->text('special_instructions')->nullable();
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'ticket_number']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_tickets');
    }
};
