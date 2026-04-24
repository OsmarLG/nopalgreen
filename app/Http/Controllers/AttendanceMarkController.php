<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkAttendanceRequest;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceMarkController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function edit(): Response
    {
        /** @var User $user */
        $user = request()->user();

        return Inertia::render('attendance-mark/index', $this->attendanceService->employeeDashboard($user));
    }

    public function store(MarkAttendanceRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $record = $this->attendanceService->mark($request, $user, $validated['mark_type'], $validated['code']);
        $label = $validated['mark_type'] === 'entry' ? 'Entrada' : 'Salida';

        return to_route('attendance-mark.edit')
            ->with('status', "{$label} registrada correctamente para {$record->attendance_date->format('d/m/Y')}.");
    }
}
