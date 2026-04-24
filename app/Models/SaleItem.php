<?php

namespace App\Models;

use Database\Factories\SaleItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @use HasFactory<SaleItemFactory> */
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'presentation_id',
        'quantity',
        'sold_quantity',
        'returned_quantity',
        'catalog_price',
        'unit_price',
        'discount_total',
        'line_total',
        'discount_note',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'sold_quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
        'catalog_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }
}
