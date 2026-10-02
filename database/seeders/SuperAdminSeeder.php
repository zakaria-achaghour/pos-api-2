<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $email = config('app.super_admin_email', 'superadmin@pos.com');
        $user = User::withTrashed()->firstOrNew(['email' => $email]);

        if ($user->exists && ($user->restaurant_id !== null || $user->trashed())) {
            throw new RuntimeException('SuperAdmin email belongs to a restaurant or deleted account. Configure APP_SUPER_ADMIN_EMAIL with a different address.');
        }

        if (! $user->exists) {
            $user->fill([
                'name' => 'Super Admin',
                'restaurant_id' => null,
                'password' => Hash::make(config('app.super_admin_password', 'password123')),
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();
        }

        // Re-running seeds never resets an existing account's password or activation state.
        $user->assignRole('SuperAdmin');
    }
}
