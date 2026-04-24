<?php

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\EmployeeDevice;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Database\Seeders\RolesAndMasterUserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view the employees page', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'empadmin01']);
    $admin->assignRole('admin');

    $employee = User::factory()->create(['username' => 'empleado01']);
    $employee->assignRole('empleado');

    $response = $this->actingAs($admin)->get(route('employees.index'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('employees/index')
        ->has('employees.data', 1)
        ->where('employees.data.0.username', $employee->username)
    );
});

test('admin can update attendance settings', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $admin = User::factory()->create(['username' => 'empadmin02']);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->patch(route('attendance-settings.update'), [
        'check_in_time' => '07:30',
        'check_out_time' => '16:30',
        'tolerance_minutes' => 12,
        'absence_after_time' => '08:15',
        'tardies_before_absence' => 4,
        'work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
    ]);

    $response->assertRedirect(route('attendance-settings.edit'));

    $settings = AppSetting::current()->fresh();

    expect(substr((string) $settings->attendance_check_in_time, 0, 5))->toBe('07:30');
    expect(substr((string) $settings->attendance_check_out_time, 0, 5))->toBe('16:30');
    expect((int) $settings->attendance_tolerance_minutes)->toBe(12);
    expect(substr((string) $settings->attendance_absence_after_time, 0, 5))->toBe('08:15');
    expect((int) $settings->attendance_tardies_before_absence)->toBe(4);
    expect($settings->attendance_work_days)->toBe(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
});

test('employee can view attendance mark page with daily codes', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'empleado02']);
    $employee->assignRole('empleado');

    $response = $this->actingAs($employee)->get(route('attendance-mark.edit'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('attendance-mark/index')
        ->where('employee.username', $employee->username)
        ->where('today.check_in_status', AttendanceRecord::STATUS_PENDING)
        ->has('today.entry_code')
        ->has('today.exit_code')
    );
});

test('employee can mark entry and preserve first seen device data', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'empleado03']);
    $employee->assignRole('empleado');
    $employee->forceFill(['attendance_starts_at' => '2026-04-01'])->save();

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:05:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:05:00'));

    $pageResponse = $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-001')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome Windows'])
        ->get(route('attendance-mark.edit'));

    $pageResponse->assertOk();

    $record = AttendanceRecord::query()->firstOrFail();
    $firstCode = $record->entry_code;

    $markResponse = $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-001')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome Windows'])
        ->post(route('attendance-mark.store'), [
            'mark_type' => AttendanceRecord::MARK_ENTRY,
            'code' => $firstCode,
        ]);

    $markResponse->assertRedirect(route('attendance-mark.edit'));

    $record->refresh();
    $device = EmployeeDevice::query()->firstOrFail();
    $firstSeenAt = $device->first_seen_at;

    expect($record->check_in_status)->toBe(AttendanceRecord::STATUS_ON_TIME);
    expect($record->check_in_device_id)->toBe($device->id);
    expect($device->browser_name)->toBe('Chrome');
    expect($device->platform_name)->toBe('Windows');

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:10:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:10:00'));

    $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-001')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Chrome Windows'])
        ->get(route('attendance-mark.edit'))
        ->assertOk();

    $device->refresh();

    expect($device->first_seen_at?->toDateTimeString())->toBe($firstSeenAt?->toDateTimeString());

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('employee can mark exit with valid code', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'empleado04']);
    $employee->assignRole('empleado');
    $employee->forceFill(['attendance_starts_at' => '2026-04-01'])->save();

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:00:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:00:00'));

    $record = AttendanceRecord::factory()->create([
        'user_id' => $employee->id,
        'attendance_date' => '2026-04-23',
        'expected_check_in_at' => '2026-04-23 08:00:00',
        'expected_check_out_at' => '2026-04-23 17:00:00',
        'absence_after_at' => '2026-04-23 09:00:00',
        'entry_code' => 'ENTRY1',
        'exit_code' => 'EXIT01',
        'check_in_at' => '2026-04-23 08:00:00',
        'check_in_status' => AttendanceRecord::STATUS_ON_TIME,
        'check_out_status' => AttendanceRecord::STATUS_PENDING,
    ]);

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 17:05:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 17:05:00'));

    $response = $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-002')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Firefox Linux'])
        ->post(route('attendance-mark.store'), [
            'mark_type' => AttendanceRecord::MARK_EXIT,
            'code' => $record->exit_code,
        ]);

    $response->assertRedirect(route('attendance-mark.edit'));

    $record->refresh();

    expect($record->check_out_status)->toBe(AttendanceRecord::STATUS_COMPLETED);
    expect($record->check_out_at)->not->toBeNull();

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('attendance classifies tardy and absent entries correctly', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create(['username' => 'empleado05']);
    $employee->assignRole('empleado');
    $employee->forceFill(['attendance_starts_at' => '2026-04-01'])->save();

    $settings = AppSetting::current();
    $settings->update([
        'attendance_check_in_time' => '08:00:00',
        'attendance_check_out_time' => '17:00:00',
        'attendance_tolerance_minutes' => 10,
        'attendance_absence_after_time' => '09:00:00',
        'attendance_tardies_before_absence' => 3,
    ]);

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:20:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:20:00'));

    $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-003')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Safari macOS'])
        ->get(route('attendance-mark.edit'))
        ->assertOk();

    $tardyRecord = AttendanceRecord::query()->firstOrFail();

    $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-003')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Safari macOS'])
        ->post(route('attendance-mark.store'), [
            'mark_type' => AttendanceRecord::MARK_ENTRY,
            'code' => $tardyRecord->entry_code,
        ])
        ->assertRedirect(route('attendance-mark.edit'));

    $tardyRecord->refresh();

    expect($tardyRecord->check_in_status)->toBe(AttendanceRecord::STATUS_TARDY);
    expect($tardyRecord->late_minutes)->toBe(20);

    AttendanceRecord::query()->delete();

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-24 09:05:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-24 09:05:00'));

    $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-003')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Safari macOS'])
        ->get(route('attendance-mark.edit'))
        ->assertOk();

    $absentRecord = AttendanceRecord::query()->firstOrFail();

    $this
        ->actingAs($employee)
        ->withCookie('employee_device_token', 'device-token-003')
        ->withServerVariables(['HTTP_USER_AGENT' => 'Mozilla/5.0 Safari macOS'])
        ->post(route('attendance-mark.store'), [
            'mark_type' => AttendanceRecord::MARK_ENTRY,
            'code' => $absentRecord->entry_code,
        ])
        ->assertRedirect(route('attendance-mark.edit'));

    $absentRecord->refresh();

    expect($absentRecord->check_in_status)->toBe(AttendanceRecord::STATUS_ABSENT);

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

test('attendance does not apply before configured start date or on non working days', function () {
    $this->seed(RolesAndMasterUserSeeder::class);

    $employee = User::factory()->create([
        'username' => 'empleado06',
        'attendance_starts_at' => '2026-04-25',
    ]);
    $employee->assignRole('empleado');

    $settings = AppSetting::current();
    $settings->update([
        'attendance_work_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    ]);

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-23 08:20:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-23 08:20:00'));

    $beforeStartResponse = $this->actingAs($employee)->get(route('attendance-mark.edit'));

    $beforeStartResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('attendance-mark/index')
        ->where('today.live_status', AttendanceRecord::STATUS_NOT_STARTED)
        ->where('today.entry_code', null)
    );

    Carbon::setTestNow(CarbonImmutable::parse('2026-04-26 08:20:00'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-26 08:20:00'));

    $offDayResponse = $this->actingAs($employee)->get(route('attendance-mark.edit'));

    $offDayResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('attendance-mark/index')
        ->where('today.live_status', AttendanceRecord::STATUS_OFF_DAY)
        ->where('today.entry_code', null)
    );

    expect(AttendanceRecord::query()->count())->toBe(0);

    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});
