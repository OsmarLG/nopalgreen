<?php

namespace App\Models;

use Database\Factories\AppSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AppSetting extends Model
{
    /** @use HasFactory<AppSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'app_name',
        'app_tagline',
        'logo_path',
        'attendance_check_in_time',
        'attendance_check_out_time',
        'attendance_tolerance_minutes',
        'attendance_absence_after_time',
        'attendance_tardies_before_absence',
        'attendance_work_days',
    ];

    protected $appends = [
        'logo_url',
        'favicon_url',
    ];

    protected $casts = [
        'attendance_work_days' => 'array',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'app_name' => 'NopalGreen',
            'app_tagline' => 'Tortilleria',
            'attendance_check_in_time' => '08:00:00',
            'attendance_check_out_time' => '17:00:00',
            'attendance_tolerance_minutes' => 10,
            'attendance_absence_after_time' => '09:00:00',
            'attendance_tardies_before_absence' => 3,
            'attendance_work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo_path === null) {
            return null;
        }

        return $this->withVersion(Storage::disk('public')->url($this->logo_path));
    }

    public function getFaviconUrlAttribute(): string
    {
        return $this->logo_url ?? $this->withVersion(asset('app-logo-default.svg'));
    }

    private function withVersion(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';
        $version = $this->updated_at?->timestamp ?? $this->created_at?->timestamp ?? now()->timestamp;

        return $url.$separator.'v='.$version;
    }
}
