<?php

namespace App\Models;

use Database\Factories\ProductionOrderConsumptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderConsumption extends Model
{
    public const ITEM_TYPE_RAW_MATERIAL = 'raw_material';

    public const ITEM_TYPE_PRODUCT = 'product';

    /** @use HasFactory<ProductionOrderConsumptionFactory> */
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'item_type',
        'item_id',
        'planned_quantity',
        'consumed_quantity',
        'unit_id',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:3',
        'consumed_quantity' => 'decimal:3',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'item_id');
    }
}
