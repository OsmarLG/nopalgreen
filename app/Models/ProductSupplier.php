<?php

namespace App\Models;

use Database\Factories\ProductSupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSupplier extends Model
{
    /** @use HasFactory<ProductSupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'supplier_id',
        'supplier_sku',
        'cost',
        'is_primary',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
