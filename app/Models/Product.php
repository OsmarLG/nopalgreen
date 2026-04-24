<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const SUPPLY_SOURCE_PRODUCTION = 'production';

    public const SUPPLY_SOURCE_SUPPLIER = 'supplier';

    public const SUPPLY_SOURCE_MIXED = 'mixed';

    public const TYPE_FINISHED = 'finished';

    public const TYPE_INTERMEDIATE = 'intermediate';

    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_unit_id',
        'supply_source',
        'product_type',
        'sale_price',
        'is_active',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function supplierLinks(): HasMany
    {
        return $this->hasMany(ProductSupplier::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }

    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }
}
