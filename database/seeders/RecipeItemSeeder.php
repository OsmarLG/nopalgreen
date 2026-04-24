<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class RecipeItemSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Unit::query()->where('code', 'kg')->first();

        if (! $kg) {
            return;
        }

        $maizBlanco = RawMaterial::query()->where('slug', 'maiz-blanco')->first();
        $maizAmarillo = RawMaterial::query()->where('slug', 'maiz-amarillo')->first();
        $aceite = RawMaterial::query()->where('slug', 'aceite')->first();
        $tortillaBlanca = Product::query()->where('slug', 'tortilla-blanca')->first();

        Recipe::query()->with('product')->get()->each(function (Recipe $recipe) use ($kg, $maizBlanco, $maizAmarillo, $aceite, $tortillaBlanca): void {
            if ($recipe->product->slug === 'tortilla-blanca' && $maizBlanco) {
                RecipeItem::query()->firstOrCreate(
                    ['recipe_id' => $recipe->id, 'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL, 'item_id' => $maizBlanco->id],
                    ['quantity' => 1, 'unit_id' => $kg->id, 'sort_order' => 1],
                );
            }

            if ($recipe->product->slug === 'tortilla-amarilla' && $maizAmarillo) {
                RecipeItem::query()->firstOrCreate(
                    ['recipe_id' => $recipe->id, 'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL, 'item_id' => $maizAmarillo->id],
                    ['quantity' => 1, 'unit_id' => $kg->id, 'sort_order' => 1],
                );
            }

            if ($recipe->product->slug === 'totopos' && $tortillaBlanca && $aceite) {
                RecipeItem::query()->firstOrCreate(
                    ['recipe_id' => $recipe->id, 'item_type' => RecipeItem::ITEM_TYPE_PRODUCT, 'item_id' => $tortillaBlanca->id],
                    ['quantity' => 1, 'unit_id' => $kg->id, 'sort_order' => 1],
                );

                RecipeItem::query()->firstOrCreate(
                    ['recipe_id' => $recipe->id, 'item_type' => RecipeItem::ITEM_TYPE_RAW_MATERIAL, 'item_id' => $aceite->id],
                    ['quantity' => 0.1, 'unit_id' => $kg->id, 'sort_order' => 2],
                );
            }
        });
    }
}
