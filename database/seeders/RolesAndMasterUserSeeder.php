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

        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'username' => 'osmarlg',
                'name' => 'Osmar Liera',
                'email' => 'osmarlg@nopalgreen.local',
                'roles' => ['master'],
            ],
            [
                'username' => 'admin',
                'name' => 'Administrador',
                'email' => 'admin@nopalgreen.local',
                'roles' => ['admin'],
            ],
            [
                'username' => 'repartidor',
                'name' => 'Repartidor',
                'email' => 'repartidor@nopalgreen.local',
                'roles' => ['empleado', 'repartidor'],
                'attendance_starts_at' => now()->toDateString(),
            ],
            [
                'username' => 'planta',
                'name' => 'Operador de Planta',
                'email' => 'planta@nopalgreen.local',
                'roles' => ['empleado', 'planta'],
                'attendance_starts_at' => now()->toDateString(),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::query()->updateOrCreate(
                ['username' => $userData['username']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make('password'),
                    'attendance_starts_at' => $userData['attendance_starts_at'] ?? null,
                ],
            );

            $user->syncRoles($userData['roles']);
            $user->syncPermissions([]);
        }
    }
}
