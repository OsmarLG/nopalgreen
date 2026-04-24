<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceSettingsController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function edit(): Response
    {
        return Inertia::render('attendance-settings/index', [
            'settings' => $this->attendanceService->settings(),
        ]);
    }

    public function update(UpdateAttendanceSettingsRequest $request): RedirectResponse
    {
        $this->attendanceService->updateSettings($request->validated());

        return to_route('attendance-settings.edit')
            ->with('status', 'Configuracion de asistencia actualizada correctamente.');
    }
}
