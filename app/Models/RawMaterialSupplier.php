<?php

namespace App\Models;

use Database\Factories\RawMaterialSupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialSupplier extends Model
{
    /** @use HasFactory<RawMaterialSupplierFactory> */
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'supplier_id',
        'supplier_sku',
        'cost',
        'is_primary',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
