<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tables DROP CONSTRAINT IF EXISTS tables_status_check');
            DB::statement("ALTER TABLE tables ADD CONSTRAINT tables_status_check CHECK (status IN ('available', 'occupied', 'reserved', 'maintenance', 'out-of-order'))");
        } else {
            Schema::table('tables', function (Blueprint $table) {
                $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'out-of-order'])->default('available')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tables DROP CONSTRAINT IF EXISTS tables_status_check');
            DB::statement("ALTER TABLE tables ADD CONSTRAINT tables_status_check CHECK (status IN ('available', 'occupied', 'reserved', 'maintenance'))");
        } else {
            Schema::table('tables', function (Blueprint $table) {
                $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance'])->default('available')->change();
            });
        }
    }
};
