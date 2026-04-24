<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $kg) {
            return;
        }

        $products = [
            [
                'name' => 'Tortilla Blanca',
                'supply_source' => Product::SUPPLY_SOURCE_PRODUCTION,
                'product_type' => Product::TYPE_FINISHED,
            ],
            [
                'name' => 'Tortilla Amarilla',
                'supply_source' => Product::SUPPLY_SOURCE_PRODUCTION,
                'product_type' => Product::TYPE_FINISHED,
            ],
            [
                'name' => 'Totopos',
                'supply_source' => Product::SUPPLY_SOURCE_MIXED,
                'product_type' => Product::TYPE_FINISHED,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    ...$product,
                    'slug' => Str::slug($product['name']),
                    'description' => 'Producto comercializable o semiterminado.',
                    'base_unit_id' => $kg->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
