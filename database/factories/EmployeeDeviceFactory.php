<?php

namespace Database\Factories;

use App\Models\EmployeeDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDevice>
 */
class EmployeeDeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fingerprint' => fake()->sha256(),
            'device_name' => 'Chrome on Windows',
            'browser_name' => 'Chrome',
            'platform_name' => 'Windows',
            'session_id' => fake()->uuid(),
            'last_ip' => fake()->ipv4(),
            'last_user_agent' => fake()->userAgent(),
            'first_seen_at' => now()->subDay(),
            'last_seen_at' => now(),
        ];
    }
}
