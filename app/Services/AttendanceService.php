<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AttendanceRecord;
use App\Models\EmployeeDevice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttendanceService
{
    private const DEFAULT_WORK_DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function __construct(private EmployeeDeviceService $employeeDeviceService) {}

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $settings = AppSetting::current();

        return [
            'check_in_time' => substr((string) $settings->attendance_check_in_time, 0, 5),
            'check_out_time' => substr((string) $settings->attendance_check_out_time, 0, 5),
            'tolerance_minutes' => (int) $settings->attendance_tolerance_minutes,
            'absence_after_time' => substr((string) $settings->attendance_absence_after_time, 0, 5),
            'tardies_before_absence' => (int) $settings->attendance_tardies_before_absence,
            'work_days' => array_values($settings->attendance_work_days ?: self::DEFAULT_WORK_DAYS),
        ];
    }

    public function updateSettings(array $data): void
    {
        $settings = AppSetting::current();
        $settings->fill([
            'attendance_check_in_time' => $data['check_in_time'].':00',
            'attendance_check_out_time' => $data['check_out_time'].':00',
            'attendance_tolerance_minutes' => $data['tolerance_minutes'],
            'attendance_absence_after_time' => $data['absence_after_time'].':00',
            'attendance_tardies_before_absence' => $data['tardies_before_absence'],
            'attendance_work_days' => array_values($data['work_days']),
        ]);
        $settings->save();
    }

    public function ensureTodayRecord(User $user): ?AttendanceRecord
    {
        return $this->ensureRecordForDate($user, CarbonImmutable::today());
    }

    public function mark(Request $request, User $user, string $markType, string $code): AttendanceRecord
    {
        $record = $this->ensureTodayRecord($user);
        abort_if($record === null, 422, 'Hoy no corresponde marcar asistencia para este empleado.');
        $device = $this->employeeDeviceService->trackFromRequest($request);
        $now = CarbonImmutable::now();

        if ($markType === AttendanceRecord::MARK_ENTRY) {
            abort_if($record->check_in_at !== null, 422, 'La entrada ya fue registrada.');
            abort_unless(hash_equals($record->entry_code, Str::upper($code)), 422, 'El codigo de entrada es invalido.');

            [$status, $lateMinutes] = $this->resolveCheckInStatus($record, $now);

            $record->fill([
                'check_in_at' => $now,
                'check_in_status' => $status,
                'late_minutes' => $lateMinutes,
                'check_in_device_id' => $device?->id,
            ])->save();

            return $record->refresh();
        }

        abort_if($record->check_in_at === null, 422, 'Primero debes marcar la entrada.');
        abort_if($record->check_out_at !== null, 422, 'La salida ya fue registrada.');
        abort_unless(hash_equals($record->exit_code, Str::upper($code)), 422, 'El codigo de salida es invalido.');

        [$status, $earlyMinutes] = $this->resolveCheckOutStatus($record, $now);

        $record->fill([
            'check_out_at' => $now,
            'check_out_status' => $status,
            'early_leave_minutes' => $earlyMinutes,
            'check_out_device_id' => $device?->id,
        ])->save();

        return $record->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeDashboard(User $employee): array
    {
        $record = $this->ensureTodayRecord($employee)?->loadMissing('checkInDevice', 'checkOutDevice');
        $currentDevice = $employee->employeeDevices()->latest('last_seen_at')->first();
        $today = CarbonImmutable::today();

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
            ],
            'settings' => $this->settings(),
            'today' => $record
                ? $this->formatAttendanceRecord($record, true)
                : $this->formatNonApplicableRecord($employee, $today, true),
            'current_device' => $currentDevice ? $this->formatDevice($currentDevice) : null,
        ];
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateEmployees(?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->role('empleado')
            ->with('employeeDevices')
            ->when($search, function ($query, string $searchTerm): void {
                $query->where(function ($nestedQuery) use ($searchTerm): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('username', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->through(function (User $employee): array {
                $todayRecord = $this->ensureTodayRecord($employee);
                $summary = $this->summaryForEmployee($employee);
                $todayStatus = $todayRecord
                    ? $this->resolveLiveStatus($todayRecord)
                    : $this->resolveNonApplicableStatus($employee, CarbonImmutable::today());

                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'username' => $employee->username,
                    'email' => $employee->email,
                    'attendance_starts_at' => ($employee->attendance_starts_at ?? $employee->created_at)?->toDateString(),
                    'today_status' => $todayStatus,
                    'check_in_at' => $todayRecord?->check_in_at?->toDateTimeString(),
                    'check_out_at' => $todayRecord?->check_out_at?->toDateTimeString(),
                    'late_minutes' => $todayRecord?->late_minutes ?? 0,
                    'tardies' => $summary['tardies'],
                    'absences' => $summary['absences'],
                    'absence_equivalents' => $summary['absence_equivalents'],
                    'devices_count' => $employee->employeeDevices->count(),
                ];
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeDetail(User $employee, ?string $from = null, ?string $to = null): array
    {
        $startDate = $from ? CarbonImmutable::parse($from) : CarbonImmutable::today()->subDays(14);
        $endDate = $to ? CarbonImmutable::parse($to) : CarbonImmutable::today();

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'email' => $employee->email,
                'attendance_starts_at' => ($employee->attendance_starts_at ?? $employee->created_at)?->toDateString(),
            ],
            'filters' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'summary' => $this->summaryForEmployee($employee, $startDate, $endDate),
            'attendance' => $this->recordsForPeriod($employee, $startDate, $endDate),
            'devices' => $employee->employeeDevices()->latest('last_seen_at')->get()->map(fn (EmployeeDevice $device) => $this->formatDevice($device))->all(),
        ];
    }

    private function ensureRecordForDate(User $user, CarbonImmutable $date): ?AttendanceRecord
    {
        if (! $this->isAttendanceApplicable($user, $date)) {
            return null;
        }

        $settings = $this->settings();
        $expectedCheckIn = $date->setTimeFromTimeString($settings['check_in_time'].':00');
        $expectedCheckOut = $date->setTimeFromTimeString($settings['check_out_time'].':00');
        $absenceAfter = $date->setTimeFromTimeString($settings['absence_after_time'].':00');

        $record = AttendanceRecord::query()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->first()
            ?? new AttendanceRecord([
                'user_id' => $user->id,
                'attendance_date' => $date->toDateString(),
            ]);

        $record->fill([
            'expected_check_in_at' => $expectedCheckIn,
            'expected_check_out_at' => $expectedCheckOut,
            'absence_after_at' => $absenceAfter,
            'tolerance_minutes' => $settings['tolerance_minutes'],
            'entry_code' => $record->entry_code ?: $this->generateCode($user, $date, AttendanceRecord::MARK_ENTRY),
            'exit_code' => $record->exit_code ?: $this->generateCode($user, $date, AttendanceRecord::MARK_EXIT),
            'check_in_status' => $record->check_in_status ?: AttendanceRecord::STATUS_PENDING,
            'check_out_status' => $record->check_out_status ?: AttendanceRecord::STATUS_PENDING,
        ]);
        $record->save();

        return $record;
    }

    /**
     * @return array{0:string,1:int}
     */
    private function resolveCheckInStatus(AttendanceRecord $record, CarbonImmutable $now): array
    {
        if ($now->lessThanOrEqualTo($record->expected_check_in_at->addMinutes($record->tolerance_minutes))) {
            return [AttendanceRecord::STATUS_ON_TIME, 0];
        }

        $lateMinutes = $record->expected_check_in_at->diffInMinutes($now);

        if ($now->greaterThanOrEqualTo(CarbonImmutable::parse($record->absence_after_at))) {
            return [AttendanceRecord::STATUS_ABSENT, $lateMinutes];
        }

        return [AttendanceRecord::STATUS_TARDY, $lateMinutes];
    }

    /**
     * @return array{0:string,1:int}
     */
    private function resolveCheckOutStatus(AttendanceRecord $record, CarbonImmutable $now): array
    {
        if ($now->greaterThanOrEqualTo(CarbonImmutable::parse($record->expected_check_out_at))) {
            return [AttendanceRecord::STATUS_COMPLETED, 0];
        }

        return [AttendanceRecord::STATUS_EARLY, $now->diffInMinutes(CarbonImmutable::parse($record->expected_check_out_at))];
    }

    private function generateCode(User $user, CarbonImmutable $date, string $markType): string
    {
        $hash = hash_hmac('sha256', "{$user->id}|{$date->toDateString()}|{$markType}", (string) config('app.key'));

        return Str::upper(substr(base_convert(substr($hash, 0, 10), 16, 36), 0, 6));
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAttendanceRecord(AttendanceRecord $record, bool $withCodes = false): array
    {
        return [
            'id' => $record->id,
            'attendance_date' => $record->attendance_date->toDateString(),
            'expected_check_in_at' => $record->expected_check_in_at->toDateTimeString(),
            'expected_check_out_at' => $record->expected_check_out_at->toDateTimeString(),
            'absence_after_at' => $record->absence_after_at->toDateTimeString(),
            'tolerance_minutes' => $record->tolerance_minutes,
            'check_in_at' => $record->check_in_at?->toDateTimeString(),
            'check_out_at' => $record->check_out_at?->toDateTimeString(),
            'check_in_status' => $record->check_in_status,
            'check_out_status' => $record->check_out_status,
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'entry_code' => $withCodes ? $record->entry_code : null,
            'exit_code' => $withCodes ? $record->exit_code : null,
            'check_in_device' => $record->checkInDevice ? $this->formatDevice($record->checkInDevice) : null,
            'check_out_device' => $record->checkOutDevice ? $this->formatDevice($record->checkOutDevice) : null,
            'live_status' => $this->resolveLiveStatus($record),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDevice(EmployeeDevice $device): array
    {
        return [
            'id' => $device->id,
            'device_name' => $device->device_name,
            'browser_name' => $device->browser_name,
            'platform_name' => $device->platform_name,
            'last_ip' => $device->last_ip,
            'last_seen_at' => $device->last_seen_at?->toDateTimeString(),
        ];
    }

    private function resolveLiveStatus(AttendanceRecord $record): string
    {
        if ($record->check_in_at !== null) {
            return $record->check_in_status;
        }

        return CarbonImmutable::now()->greaterThanOrEqualTo(CarbonImmutable::parse($record->absence_after_at))
            ? AttendanceRecord::STATUS_ABSENT
            : AttendanceRecord::STATUS_PENDING;
    }

    private function isAttendanceApplicable(User $user, CarbonImmutable $date): bool
    {
        if (! $user->hasRole('empleado')) {
            return false;
        }

        $attendanceStartDate = CarbonImmutable::parse(
            ($user->attendance_starts_at ?? $user->created_at)->toDateString(),
        );

        if ($date->lessThan($attendanceStartDate)) {
            return false;
        }

        return in_array(strtolower($date->englishDayOfWeek), $this->settings()['work_days'], true);
    }

    private function resolveNonApplicableStatus(User $user, CarbonImmutable $date): string
    {
        $attendanceStartDate = CarbonImmutable::parse(
            ($user->attendance_starts_at ?? $user->created_at)->toDateString(),
        );

        if ($date->lessThan($attendanceStartDate)) {
            return AttendanceRecord::STATUS_NOT_STARTED;
        }

        return AttendanceRecord::STATUS_OFF_DAY;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recordsForPeriod(User $employee, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        /** @var Collection<int, AttendanceRecord> $records */
        $records = $employee->attendanceRecords()
            ->with(['checkInDevice', 'checkOutDevice'])
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get()
            ->keyBy(fn (AttendanceRecord $record) => $record->attendance_date->toDateString());

        $history = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $dateKey = CarbonImmutable::parse($date)->toDateString();
            $currentDate = CarbonImmutable::parse($dateKey);
            $record = $records->get($dateKey) ?? $this->ensureRecordForDate($employee, $currentDate);
            $history[] = $record
                ? $this->formatAttendanceRecord($record)
                : $this->formatNonApplicableRecord($employee, $currentDate);
        }

        return array_reverse($history);
    }

    /**
     * @return array{attendances:int,tardies:int,absences:int,absence_equivalents:int}
     */
    private function summaryForEmployee(User $employee, ?CarbonImmutable $startDate = null, ?CarbonImmutable $endDate = null): array
    {
        $startDate ??= CarbonImmutable::today()->subDays(29);
        $endDate ??= CarbonImmutable::today();
        $records = $this->recordsForPeriod($employee, $startDate, $endDate);
        $settings = $this->settings();

        $attendances = 0;
        $tardies = 0;
        $absences = 0;

        foreach ($records as $record) {
            if (in_array($record['live_status'], [AttendanceRecord::STATUS_OFF_DAY, AttendanceRecord::STATUS_NOT_STARTED], true)) {
                continue;
            }

            if (in_array($record['check_in_status'], [AttendanceRecord::STATUS_ON_TIME, AttendanceRecord::STATUS_TARDY], true)) {
                $attendances++;
            }

            if ($record['check_in_status'] === AttendanceRecord::STATUS_TARDY) {
                $tardies++;
            }

            if ($record['live_status'] === AttendanceRecord::STATUS_ABSENT || $record['check_in_status'] === AttendanceRecord::STATUS_ABSENT) {
                $absences++;
            }
        }

        return [
            'attendances' => $attendances,
            'tardies' => $tardies,
            'absences' => $absences,
            'absence_equivalents' => intdiv($tardies, max(1, $settings['tardies_before_absence'])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatNonApplicableRecord(User $user, CarbonImmutable $date, bool $withCodes = false): array
    {
        $settings = $this->settings();
        $status = $this->resolveNonApplicableStatus($user, $date);

        return [
            'id' => 0,
            'attendance_date' => $date->toDateString(),
            'expected_check_in_at' => $date->setTimeFromTimeString($settings['check_in_time'].':00')->toDateTimeString(),
            'expected_check_out_at' => $date->setTimeFromTimeString($settings['check_out_time'].':00')->toDateTimeString(),
            'absence_after_at' => $date->setTimeFromTimeString($settings['absence_after_time'].':00')->toDateTimeString(),
            'tolerance_minutes' => $settings['tolerance_minutes'],
            'check_in_at' => null,
            'check_out_at' => null,
            'check_in_status' => $status,
            'check_out_status' => $status,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'entry_code' => $withCodes ? null : null,
            'exit_code' => $withCodes ? null : null,
            'check_in_device' => null,
            'check_out_device' => null,
            'live_status' => $status,
        ];
    }
}
