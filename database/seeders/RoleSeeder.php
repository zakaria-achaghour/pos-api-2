<?php
// filepath: database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Menu management
            'manage-menu',
            'view-menu',
            
            // Order management
            'create-orders',
            'view-orders',
            'update-orders',
            'delete-orders',
            
            // Kitchen management
            'manage-kitchen',
            'view-kitchen',
            
            // Staff management
            'manage-staff',
            'view-staff',
            'manage-attendance',
            
            // Table management
            'manage-tables',
            'view-tables',
            
            // Reports and analytics
            'view-reports',
            'export-reports',
            'view-analytics',
            
            // Restaurant management
            'manage-restaurant',
            'view-restaurant-settings',
            
            // Super admin permissions
            'manage-all-restaurants',
            'impersonate-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles with permissions
        $roles = [
            'SuperAdmin' => [
                'manage-all-restaurants',
                'impersonate-users',
                'manage-menu',
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'delete-orders',
                'manage-kitchen',
                'view-kitchen',
                'manage-staff',
                'view-staff',
                'manage-attendance',
                'manage-tables',
                'view-tables',
                'view-reports',
                'export-reports',
                'view-analytics',
                'manage-restaurant',
                'view-restaurant-settings',
            ],
            
            'Owner' => [
                'manage-menu',
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'delete-orders',
                'manage-kitchen',
                'view-kitchen',
                'manage-staff',
                'view-staff',
                'manage-attendance',
                'manage-tables',
                'view-tables',
                'view-reports',
                'export-reports',
                'view-analytics',
                'manage-restaurant',
                'view-restaurant-settings',
            ],
            
            'Manager' => [
                'manage-menu',
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'manage-kitchen',
                'view-kitchen',
                'manage-staff',
                'view-staff',
                'manage-attendance',
                'manage-tables',
                'view-tables',
                'view-reports',
                'export-reports',
                'view-analytics',
            ],
            
            'Cashier' => [
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'view-tables',
            ],
            
            'Waiter' => [
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'view-tables',
            ],
            
            'Kitchen' => [
                'view-menu',
                'view-orders',
                'manage-kitchen',
                'view-kitchen',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($permissions);
        }
    }
}