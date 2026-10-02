<?php

// filepath: database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we always work with fresh permission cache when seeding
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create permissions with guard_name
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

            // Payments
            'manage-payments',

            // Restaurant management
            'manage-restaurant',
            'view-restaurant-settings',

            // Super admin permissions
            'manage-all-restaurants',
            'impersonate-users',

            // Role and permission management
            'view-roles',
            'manage-roles',
            'view-permissions',
            'manage-permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api']
            );
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
                'manage-payments',
                'manage-restaurant',
                'view-restaurant-settings',
                'view-roles',
                'manage-roles',
                'view-permissions',
                'manage-permissions',
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
                'manage-payments',
                'manage-restaurant',
                'view-restaurant-settings',
                'view-roles', // Need to view roles for staff management
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
                'manage-payments',
                'view-roles', // Need to view roles for staff management
            ],

            'Cashier' => [
                'view-menu',
                'create-orders',
                'view-orders',
                'update-orders',
                'view-tables',
                'manage-payments',
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

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api']
            );
            $role->syncPermissions($rolePermissions);
        }
    }
}
