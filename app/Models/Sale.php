<?php

namespace App\Models;

use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const TYPE_DIRECT = 'direct';

    public const TYPE_DELIVERY = 'delivery';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPES = [
        self::TYPE_DIRECT,
        self::TYPE_DELIVERY,
    ];

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ASSIGNED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'folio',
        'customer_id',
        'delivery_user_id',
        'sale_type',
        'status',
        'sale_date',
        'delivery_date',
        'completed_at',
        'subtotal',
        'discount_total',
        'total',
        'notes',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'delivery_date' => 'datetime',
        'completed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
