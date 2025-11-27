<?php
// filepath: database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

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

        $this->ensureSuperAdminUserExists();
    }

    /**
     * Make sure there is at least one active SuperAdmin account that can log in.
     */
    private function ensureSuperAdminUserExists(): void
    {
        $email = config('app.super_admin_email', 'superadmin@pos.com');
        $password = config('app.super_admin_password', 'password123');

        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->fill([
                'name' => 'Super Admin',
                'restaurant_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->password = Hash::make($password);
            $user->save();
        }

        if (! $user->hasRole('SuperAdmin')) {
            $user->assignRole('SuperAdmin');
        }
    }
}
