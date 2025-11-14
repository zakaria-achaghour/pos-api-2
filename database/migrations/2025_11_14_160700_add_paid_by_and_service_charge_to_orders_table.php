<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'service_charge_amount')) {
                $table->decimal('service_charge_amount', 10, 2)->default(0)->after('tax_amount');
            }

            if (!Schema::hasColumn('orders', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('paid_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'paid_by')) {
                $table->dropForeign(['paid_by']);
                $table->dropColumn('paid_by');
            }

            if (Schema::hasColumn('orders', 'service_charge_amount')) {
                $table->dropColumn('service_charge_amount');
            }
        });
    }
};
