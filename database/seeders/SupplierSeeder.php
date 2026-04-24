<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Maices del Norte', 'contact_name' => 'Laura Perez'],
            ['name' => 'Harinas y Aceites Ramos', 'contact_name' => 'Pedro Ramos'],
            ['name' => 'Distribuidora El Milagro', 'contact_name' => 'Ana Ortiz'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(
                ['name' => $supplier['name']],
                [
                    ...$supplier,
                    'phone' => fake()->phoneNumber(),
                    'email' => fake()->safeEmail(),
                    'address' => fake()->address(),
                    'is_active' => true,
                ],
            );
        }
    }
}
