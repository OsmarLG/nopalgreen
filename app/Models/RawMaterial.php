<?php

namespace App\Models;

use Database\Factories\RawMaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    /** @use HasFactory<RawMaterialFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_unit_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(RawMaterialPresentation::class);
    }

    public function supplierLinks(): HasMany
    {
        return $this->hasMany(RawMaterialSupplier::class);
    }
}
