<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Access;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndMasterUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Access::PERMISSIONS as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (Access::rolePermissions() as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }

        $masterUser = User::query()->updateOrCreate(
            ['username' => 'osmarlg'],
            [
                'name' => 'Osmar Liera',
                'email' => 'osmarlg@nopalgreen.local',
                'password' => Hash::make('password'),
            ],
        );

        $masterUser->syncRoles(['master']);
        $masterUser->syncPermissions([]);
    }
}
