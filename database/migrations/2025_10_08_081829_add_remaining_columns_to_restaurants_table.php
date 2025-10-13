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
        Schema::table('restaurants', function (Blueprint $table) {
            // Add remaining missing columns if they don't exist
            if (!Schema::hasColumn('restaurants', 'slug')) {
                $table->string('slug')->unique()->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('restaurants', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            
            if (!Schema::hasColumn('restaurants', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('city');
            }
            
            if (!Schema::hasColumn('restaurants', 'country')) {
                $table->string('country')->nullable()->after('postal_code');
            }
            
            if (!Schema::hasColumn('restaurants', 'website')) {
                $table->string('website')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('restaurants', 'cuisine_type')) {
                $table->string('cuisine_type')->nullable()->after('website');
            }
            
            if (!Schema::hasColumn('restaurants', 'service_charge')) {
                $table->decimal('service_charge', 5, 2)->default(0)->after('tax_rate');
            }
            
            if (!Schema::hasColumn('restaurants', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('service_charge');
            }
            
            if (!Schema::hasColumn('restaurants', 'subscription_type')) {
                $table->enum('subscription_type', ['basic', 'premium', 'enterprise'])->default('basic')->after('status');
            }
            
            if (!Schema::hasColumn('restaurants', 'subscription_start')) {
                $table->date('subscription_start')->nullable()->after('subscription_type');
            }
            
            if (!Schema::hasColumn('restaurants', 'subscription_end')) {
                $table->date('subscription_end')->nullable()->after('subscription_start');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $columnsToCheck = [
                'slug', 'city', 'postal_code', 'country', 'website', 
                'cuisine_type', 'service_charge', 'status', 
                'subscription_type', 'subscription_start', 'subscription_end'
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
