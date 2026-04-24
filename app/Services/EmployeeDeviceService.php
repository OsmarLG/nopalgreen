<?php

namespace App\Services;

use App\Models\EmployeeDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class EmployeeDeviceService
{
    public function trackFromRequest(Request $request): ?EmployeeDevice
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! $user->hasRole('empleado')) {
            return null;
        }

        $token = $request->cookie('employee_device_token');

        if (! is_string($token) || $token === '') {
            $token = (string) Str::uuid();
            Cookie::queue(cookie('employee_device_token', $token, 60 * 24 * 365 * 5));
        }

        $fingerprint = hash('sha256', $token);

        $device = EmployeeDevice::query()->firstOrNew([
            'user_id' => $user->id,
            'fingerprint' => $fingerprint,
        ]);

        $device->fill([
            'device_name' => trim($this->resolveBrowserName($request->userAgent()).' on '.$this->resolvePlatformName($request->userAgent())),
            'browser_name' => $this->resolveBrowserName($request->userAgent()),
            'platform_name' => $this->resolvePlatformName($request->userAgent()),
            'session_id' => $request->session()->getId(),
            'last_ip' => $request->ip(),
            'last_user_agent' => $request->userAgent(),
            'first_seen_at' => $device->exists ? $device->first_seen_at : now(),
            'last_seen_at' => now(),
        ]);
        $device->save();

        return $device;
    }

    private function resolveBrowserName(?string $userAgent): string
    {
        $agent = Str::lower($userAgent ?? '');

        return match (true) {
            str_contains($agent, 'edg') => 'Edge',
            str_contains($agent, 'chrome') => 'Chrome',
            str_contains($agent, 'firefox') => 'Firefox',
            str_contains($agent, 'safari') => 'Safari',
            str_contains($agent, 'opera') => 'Opera',
            default => 'Browser',
        };
    }

    private function resolvePlatformName(?string $userAgent): string
    {
        $agent = Str::lower($userAgent ?? '');

        return match (true) {
            str_contains($agent, 'windows') => 'Windows',
            str_contains($agent, 'android') => 'Android',
            str_contains($agent, 'iphone'), str_contains($agent, 'ipad'), str_contains($agent, 'ios') => 'iOS',
            str_contains($agent, 'mac os'), str_contains($agent, 'macintosh') => 'macOS',
            str_contains($agent, 'linux') => 'Linux',
            default => 'Unknown OS',
        };
    }
}
