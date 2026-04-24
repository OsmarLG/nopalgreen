<?php

namespace App\Models;

use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ON_TIME = 'on_time';

    public const STATUS_TARDY = 'tardy';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_EARLY = 'early';

    public const STATUS_OFF_DAY = 'off_day';

    public const STATUS_NOT_STARTED = 'not_started';

    public const MARK_ENTRY = 'entry';

    public const MARK_EXIT = 'exit';

    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'expected_check_in_at',
        'expected_check_out_at',
        'absence_after_at',
        'tolerance_minutes',
        'entry_code',
        'exit_code',
        'check_in_at',
        'check_out_at',
        'check_in_status',
        'check_out_status',
        'late_minutes',
        'early_leave_minutes',
        'check_in_device_id',
        'check_out_device_id',
        'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'expected_check_in_at' => 'datetime',
        'expected_check_out_at' => 'datetime',
        'absence_after_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkInDevice(): BelongsTo
    {
        return $this->belongsTo(EmployeeDevice::class, 'check_in_device_id');
    }

    public function checkOutDevice(): BelongsTo
    {
        return $this->belongsTo(EmployeeDevice::class, 'check_out_device_id');
    }
}
