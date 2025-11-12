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
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'removed_ingredients')) {
                $table->json('removed_ingredients')->nullable()->after('special_instructions');
            }
            if (!Schema::hasColumn('order_items', 'added_extras')) {
                $table->json('added_extras')->nullable()->after('removed_ingredients');
            }
            if (!Schema::hasColumn('order_items', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('added_extras')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('order_items', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropColumn(['removed_ingredients', 'added_extras', 'created_by', 'updated_by']);
        });
    }
};
