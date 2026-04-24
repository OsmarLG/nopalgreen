<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Recipe;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductionOrderSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()->where('slug', 'tortilla-blanca')->first();
        $recipe = Recipe::query()->whereBelongsTo($product)->first();
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $product || ! $recipe || ! $kg) {
            return;
        }

        ProductionOrder::query()->firstOrCreate(
            ['folio' => 'OP-000001'],
            [
                'product_id' => $product->id,
                'recipe_id' => $recipe->id,
                'planned_quantity' => 50,
                'produced_quantity' => 50,
                'unit_id' => $kg->id,
                'status' => ProductionOrder::STATUS_COMPLETED,
                'scheduled_for' => now()->subHours(4),
                'started_at' => now()->subHours(3),
                'finished_at' => now()->subHours(2),
                'notes' => 'Orden inicial de ejemplo.',
            ],
        );
    }
}
