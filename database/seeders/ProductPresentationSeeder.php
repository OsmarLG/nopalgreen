<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductPresentationSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $kg) {
            return;
        }

        Product::query()->get()->each(function (Product $product) use ($kg): void {
            ProductPresentation::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => 'Kilogramo',
                ],
                [
                    'quantity' => 1,
                    'unit_id' => $kg->id,
                    'barcode' => fake()->ean13(),
                    'is_active' => true,
                ],
            );
        });
    }
}
