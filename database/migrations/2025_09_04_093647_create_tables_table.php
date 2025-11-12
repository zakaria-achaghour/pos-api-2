<?php
// filepath: database/migrations/[timestamp]_create_tables_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->string('number'); // This column was missing
            $table->integer('capacity');
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])->default('available');
            $table->string('section')->nullable();
            $table->json('location')->nullable();
            // Add features as JSON array to store table features
            $table->json('features')->nullable();
            $table->integer('grid_x')->nullable();
            $table->integer('grid_y')->nullable();
            $table->string('qr_code')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            // Indexes
            $table->unique(['restaurant_id', 'number']);
            $table->unique(['restaurant_id', 'qr_code']);
            $table->index(['restaurant_id', 'status']);
            $table->index(['restaurant_id', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};