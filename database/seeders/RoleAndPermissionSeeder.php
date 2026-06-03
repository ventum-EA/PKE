<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        // Sanctum SPA mode authenticates via the 'web' guard under the hood.
        // Roles and permissions must use the same guard.
        $guard = 'web';

        // Fix any existing records that used the wrong guard ('sanctum')
        Permission::where('guard_name', 'sanctum')->update(['guard_name' => $guard]);
        Role::where('guard_name', 'sanctum')->update(['guard_name' => $guard]);

        $permissions = ['manage users', 'manage games', 'access training'];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => $guard],
            );
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => $guard],
        );
        $adminRole->syncPermissions(Permission::where('guard_name', $guard)->get());

        $userRole = Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => $guard],
        );
        $userRole->syncPermissions(['manage games', 'access training']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
