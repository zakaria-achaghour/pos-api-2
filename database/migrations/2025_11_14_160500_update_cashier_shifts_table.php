<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('cashier_shifts', 'restaurant_id')) {
                $table->foreignId('restaurant_id')->after('id')->constrained()->onDelete('cascade');
            }

            if (!Schema::hasColumn('cashier_shifts', 'user_id')) {
                $table->foreignId('user_id')->after('restaurant_id')->constrained()->onDelete('cascade');
            }

            if (!Schema::hasColumn('cashier_shifts', 'opened_at')) {
                $table->timestamp('opened_at')->after('user_id');
            }

            if (!Schema::hasColumn('cashier_shifts', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('opened_at');
            }

            if (!Schema::hasColumn('cashier_shifts', 'opening_amount')) {
                $table->decimal('opening_amount', 10, 2)->default(0)->after('closed_at');
            }

            if (!Schema::hasColumn('cashier_shifts', 'closing_amount')) {
                $table->decimal('closing_amount', 10, 2)->nullable()->after('opening_amount');
            }

            if (!Schema::hasColumn('cashier_shifts', 'notes')) {
                $table->text('notes')->nullable()->after('closing_amount');
            }

            $table->index(['restaurant_id', 'opened_at']);
            $table->index(['restaurant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('cashier_shifts', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('cashier_shifts', 'closing_amount')) {
                $table->dropColumn('closing_amount');
            }
            if (Schema::hasColumn('cashier_shifts', 'opening_amount')) {
                $table->dropColumn('opening_amount');
            }
            if (Schema::hasColumn('cashier_shifts', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
            if (Schema::hasColumn('cashier_shifts', 'opened_at')) {
                $table->dropColumn('opened_at');
            }
            if (Schema::hasColumn('cashier_shifts', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('cashier_shifts', 'restaurant_id')) {
                $table->dropForeign(['restaurant_id']);
                $table->dropColumn('restaurant_id');
            }
        });
    }
};
