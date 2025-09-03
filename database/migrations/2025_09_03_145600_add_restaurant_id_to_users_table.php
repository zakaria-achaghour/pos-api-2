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
        Schema::table('users', function (Blueprint $table) {
             $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            // unique email per tenant
            $table->unique(['restaurant_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'email']);
            $table->dropConstrainedForeignId('restaurant_id');
        });
    }
};
