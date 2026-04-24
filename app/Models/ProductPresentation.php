<?php

namespace App\Models;

use Database\Factories\ProductPresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPresentation extends Model
{
    /** @use HasFactory<ProductPresentationFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'quantity',
        'unit_id',
        'barcode',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
