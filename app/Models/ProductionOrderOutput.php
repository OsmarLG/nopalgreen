<?php

namespace App\Models;

use Database\Factories\ProductionOrderOutputFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderOutput extends Model
{
    /** @use HasFactory<ProductionOrderOutputFactory> */
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_id',
        'quantity',
        'unit_id',
        'is_main_output',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_main_output' => 'boolean',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
