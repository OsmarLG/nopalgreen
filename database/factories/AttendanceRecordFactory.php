<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
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
            'attendance_date' => now()->toDateString(),
            'expected_check_in_at' => now()->setTime(8, 0),
            'expected_check_out_at' => now()->setTime(17, 0),
            'absence_after_at' => now()->setTime(9, 0),
            'tolerance_minutes' => 10,
            'entry_code' => 'ENT123',
            'exit_code' => 'SAL123',
            'check_in_status' => AttendanceRecord::STATUS_PENDING,
            'check_out_status' => AttendanceRecord::STATUS_PENDING,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
        ];
    }
}
