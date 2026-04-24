<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request): Response
    {
        return Inertia::render('employees/index', [
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
            'employees' => $this->attendanceService->paginateEmployees($request->string('search')->toString()),
        ]);
    }

    public function show(Request $request, User $employee): Response
    {
        abort_unless($employee->hasRole('empleado'), 404);

        return Inertia::render('employees/show', $this->attendanceService->employeeDetail(
            $employee,
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        ));
    }
}
