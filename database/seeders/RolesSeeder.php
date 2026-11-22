<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage_pages', 'manage_posts', 'manage_services', 'manage_team', 'manage_portfolios',
            'manage_seo', 'manage_google', 'manage_users', 'manage_settings', 'manage_redirects',
            'manage_appointments',
        ];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions($permissions); // full access

        $marketing = Role::firstOrCreate(['name' => 'marketing_manager', 'guard_name' => 'web']);
        $marketing->syncPermissions([
            'manage_posts', 'manage_pages', 'manage_services', 'manage_portfolios',
            'manage_seo', 'manage_google', 'manage_redirects', 'manage_appointments',
        ]);

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@hazemlaravel.test'],
            ['name' => 'Administrator', 'password' => Hash::make('password')]
        );
        $adminUser->syncRoles(['admin']);

        $mktUser = User::firstOrCreate(
            ['email' => 'marketing@hazemlaravel.test'],
            ['name' => 'Marketing Manager', 'password' => Hash::make('password')]
        );
        $mktUser->syncRoles(['marketing_manager']);

        $this->command->info('Roles & users ready (admin@ / marketing@ hazemlaravel.test — password: password).');
    }
}
