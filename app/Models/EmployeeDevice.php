<?php

namespace App\Models;

use Database\Factories\EmployeeDeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDevice extends Model
{
    /** @use HasFactory<EmployeeDeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fingerprint',
        'device_name',
        'browser_name',
        'platform_name',
        'session_id',
        'last_ip',
        'last_user_agent',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkInAttendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'check_in_device_id');
    }

    public function checkOutAttendances(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'check_out_device_id');
    }
}
