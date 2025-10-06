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
        Schema::create('table_analytics', function (Blueprint $table) {
             $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_seatings')->default(0);
            $table->decimal('total_revenue', 10, 2)->default(0);
            $table->integer('total_duration_minutes')->default(0); // total time occupied
            $table->integer('average_duration_minutes')->default(0);
            $table->decimal('occupancy_rate', 5, 2)->default(0); // percentage
            $table->timestamps();
            
            $table->unique(['restaurant_id', 'table_id', 'date']);
            $table->index(['restaurant_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_analytics');
    }
};
