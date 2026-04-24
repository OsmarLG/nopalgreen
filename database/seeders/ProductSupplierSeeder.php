<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSupplier;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProductSupplierSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::query()->first();

        if (! $supplier) {
            return;
        }

        Product::query()->get()->each(function (Product $product) use ($supplier): void {
            ProductSupplier::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'supplier_id' => $supplier->id,
                ],
                [
                    'supplier_sku' => 'PR-'.$product->id,
                    'cost' => 120,
                    'is_primary' => $product->supply_source !== Product::SUPPLY_SOURCE_PRODUCTION,
                ],
            );
        });
    }
}
