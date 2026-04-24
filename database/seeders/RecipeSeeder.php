<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $kg) {
            return;
        }

        Product::query()->whereIn('slug', ['tortilla-blanca', 'tortilla-amarilla', 'totopos'])
            ->get()
            ->each(function (Product $product) use ($kg): void {
                Recipe::query()->firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'version' => 1,
                    ],
                    [
                        'name' => 'Formula '.$product->name,
                        'yield_quantity' => 1,
                        'yield_unit_id' => $kg->id,
                        'is_active' => true,
                    ],
                );
            });
    }
}
