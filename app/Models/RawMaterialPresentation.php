<?php

namespace App\Models;

use Database\Factories\RawMaterialPresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialPresentation extends Model
{
    /** @use HasFactory<RawMaterialPresentationFactory> */
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
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

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
