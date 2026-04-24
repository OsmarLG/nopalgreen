<?php

namespace App\Models;

use Database\Factories\FinanceTransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    public const TYPE_INCOME = 'income';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_DEBT = 'debt';

    public const TYPE_COLLECTION = 'collection';

    public const TYPE_PAYMENT = 'payment';

    public const TYPE_LOSS = 'loss';

    public const TYPE_REFUND = 'refund';

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_SALE = 'sale';

    public const SOURCE_PRODUCTION = 'production';

    public const SOURCE_WASTE = 'waste';

    public const TRANSACTION_TYPES = [
        self::TYPE_INCOME,
        self::TYPE_EXPENSE,
        self::TYPE_DEBT,
        self::TYPE_COLLECTION,
        self::TYPE_PAYMENT,
        self::TYPE_LOSS,
        self::TYPE_REFUND,
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_POSTED,
        self::STATUS_CANCELLED,
    ];

    /** @use HasFactory<FinanceTransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'folio',
        'transaction_type',
        'direction',
        'source',
        'concept',
        'detail',
        'amount',
        'status',
        'is_manual',
        'affects_balance',
        'created_by',
        'reference_type',
        'reference_id',
        'occurred_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_manual' => 'boolean',
        'affects_balance' => 'boolean',
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
