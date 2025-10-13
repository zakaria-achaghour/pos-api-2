<?php
// filepath: database/migrations/[timestamp]_add_missing_columns_to_restaurants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('restaurants', 'subdomain')) {
                $table->string('subdomain')->unique()->after('name');
            }
            
            if (!Schema::hasColumn('restaurants', 'address')) {
                $table->text('address')->nullable()->after('subdomain');
            }
            
            if (!Schema::hasColumn('restaurants', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('restaurants', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            
            if (!Schema::hasColumn('restaurants', 'timezone')) {
                $table->string('timezone')->default('UTC')->after('email');
            }
            
            if (!Schema::hasColumn('restaurants', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('timezone');
            }
            
            if (!Schema::hasColumn('restaurants', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('currency');
            }
            
            if (!Schema::hasColumn('restaurants', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('tax_rate');
            }
            
            if (!Schema::hasColumn('restaurants', 'settings')) {
                $table->json('settings')->nullable()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $columnsToCheck = [
                'subdomain', 'address', 'phone', 'email', 
                'timezone', 'currency', 'tax_rate', 'is_active', 'settings'
            ];
            
            $existingColumns = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};