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
        Schema::table('tables', function (Blueprint $table) {
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])->default('available')->change();
            $table->integer('grid_x')->nullable();
            $table->integer('grid_y')->nullable();
            $table->timestamp('last_occupied_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->enum('status', ['available', 'occupied'])->default('available')->change();
            $table->dropColumn(['grid_x', 'grid_y', 'last_occupied_at', 'reserved_at', 'reserved_by']);
        });
    }
};
