<?php

namespace App\Http\Middleware;

use App\Services\EmployeeDeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackEmployeeDevice
{
    public function __construct(private EmployeeDeviceService $employeeDeviceService) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null) {
            $this->employeeDeviceService->trackFromRequest($request);
        }

        return $next($request);
    }
}
