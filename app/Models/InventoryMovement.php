<?php

namespace App\Models;

use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    public const ITEM_TYPE_RAW_MATERIAL = 'raw_material';

    public const ITEM_TYPE_PRODUCT = 'product';

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_PRODUCTION_OUTPUT = 'production_output';

    public const TYPE_PRODUCTION_CONSUMPTION = 'production_consumption';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_RETURN = 'return';

    public const TYPE_WASTE = 'waste';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_DISPATCH = 'sale_dispatch';

    public const MOVEMENT_TYPES = [
        self::TYPE_PURCHASE,
        self::TYPE_PRODUCTION_OUTPUT,
        self::TYPE_PRODUCTION_CONSUMPTION,
        self::TYPE_ADJUSTMENT,
        self::TYPE_RETURN,
        self::TYPE_WASTE,
        self::TYPE_TRANSFER,
        self::TYPE_SALE,
        self::TYPE_SALE_DISPATCH,
    ];

    /** @use HasFactory<InventoryMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'item_type',
        'item_id',
        'movement_type',
        'direction',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'notes',
        'moved_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
