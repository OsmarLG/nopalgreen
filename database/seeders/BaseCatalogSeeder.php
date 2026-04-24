<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UnitSeeder::class,
            SupplierSeeder::class,
            RawMaterialSeeder::class,
            ProductSeeder::class,
            RawMaterialPresentationSeeder::class,
            ProductPresentationSeeder::class,
            RawMaterialSupplierSeeder::class,
            ProductSupplierSeeder::class,
            RecipeSeeder::class,
            RecipeItemSeeder::class,
        ]);
    }
}
