<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\Sale;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $roleScope = $this->resolveRoleScope($user);

        return Inertia::render('dashboard', [
            'roleScope' => $roleScope,
            'cards' => $this->buildCards($user, $roleScope),
            'lists' => $this->buildLists($user, $roleScope),
        ]);
    }

    private function resolveRoleScope(User $user): string
    {
        if ($user->hasAnyRole(['master', 'admin'])) {
            return 'admin';
        }

        if ($user->hasRole('repartidor')) {
            return 'repartidor';
        }

        if ($user->hasRole('planta')) {
            return 'planta';
        }

        if ($user->hasRole('empleado')) {
            return 'empleado';
        }

        return 'general';
    }

    /**
     * @return list<array{title:string,value:string,description:string,tone:string}>
     */
    private function buildCards(User $user, string $roleScope): array
    {
        return match ($roleScope) {
            'empleado' => $this->buildEmployeeCards($user),
            'repartidor' => [
                [
                    'title' => 'Por entregar',
                    'value' => (string) Sale::query()
                        ->where('delivery_user_id', $user->id)
                        ->where('status', Sale::STATUS_ASSIGNED)
                        ->count(),
                    'description' => 'Pedidos asignados pendientes de liquidacion.',
                    'tone' => 'nopal',
                ],
                [
                    'title' => 'Entregadas',
                    'value' => (string) Sale::query()
                        ->where('delivery_user_id', $user->id)
                        ->where('status', Sale::STATUS_COMPLETED)
                        ->count(),
                    'description' => 'Pedidos ya cerrados por este repartidor.',
                    'tone' => 'maiz',
                ],
                [
                    'title' => 'Clientes en ruta',
                    'value' => (string) Sale::query()
                        ->where('delivery_user_id', $user->id)
                        ->where('status', Sale::STATUS_ASSIGNED)
                        ->whereNotNull('customer_id')
                        ->distinct('customer_id')
                        ->count('customer_id'),
                    'description' => 'Clientes pendientes dentro de la ruta activa.',
                    'tone' => 'stone',
                ],
            ],
            'planta' => [
                [
                    'title' => 'Ordenes activas',
                    'value' => (string) ProductionOrder::query()
                        ->whereIn('status', [
                            ProductionOrder::STATUS_PLANNED,
                            ProductionOrder::STATUS_IN_PROGRESS,
                        ])
                        ->count(),
                    'description' => 'Ordenes listas o en proceso dentro de planta.',
                    'tone' => 'nopal',
                ],
                [
                    'title' => 'Completadas hoy',
                    'value' => (string) ProductionOrder::query()
                        ->where('status', ProductionOrder::STATUS_COMPLETED)
                        ->whereDate('finished_at', today())
                        ->count(),
                    'description' => 'Produccion cerrada durante el dia de hoy.',
                    'tone' => 'maiz',
                ],
                [
                    'title' => 'Recetas activas',
                    'value' => (string) Recipe::query()->where('is_active', true)->count(),
                    'description' => 'Formulas disponibles para operar en produccion.',
                    'tone' => 'stone',
                ],
            ],
            default => [
                [
                    'title' => 'Ventas completadas hoy',
                    'value' => (string) Sale::query()
                        ->where('status', Sale::STATUS_COMPLETED)
                        ->whereDate('completed_at', today())
                        ->count(),
                    'description' => 'Ventas cerradas durante el dia.',
                    'tone' => 'nopal',
                ],
                [
                    'title' => 'Repartos pendientes',
                    'value' => (string) Sale::query()
                        ->where('sale_type', Sale::TYPE_DELIVERY)
                        ->where('status', Sale::STATUS_ASSIGNED)
                        ->count(),
                    'description' => 'Pedidos por entregar y liquidar.',
                    'tone' => 'maiz',
                ],
                [
                    'title' => 'Produccion activa',
                    'value' => (string) ProductionOrder::query()
                        ->whereIn('status', [
                            ProductionOrder::STATUS_PLANNED,
                            ProductionOrder::STATUS_IN_PROGRESS,
                        ])
                        ->count(),
                    'description' => 'Ordenes que siguen vivas en planta.',
                    'tone' => 'stone',
                ],
                [
                    'title' => 'Compras recibidas hoy',
                    'value' => (string) Purchase::query()
                        ->where('status', Purchase::STATUS_RECEIVED)
                        ->whereDate('purchased_at', today())
                        ->count(),
                    'description' => 'Entradas de proveedor confirmadas hoy.',
                    'tone' => 'nopal',
                ],
                [
                    'title' => 'Empleados a tiempo',
                    'value' => (string) $this->attendanceCountForToday(AttendanceRecord::STATUS_ON_TIME),
                    'description' => 'Personal que ya marco entrada dentro del horario esperado.',
                    'tone' => 'maiz',
                ],
                [
                    'title' => 'Retardos y faltas',
                    'value' => (string) (
                        $this->attendanceCountForToday(AttendanceRecord::STATUS_TARDY)
                        + $this->attendanceCountForToday(AttendanceRecord::STATUS_ABSENT)
                    ),
                    'description' => 'Empleados con atraso o falta registrada en el dia.',
                    'tone' => 'stone',
                ],
            ],
        };
    }

    /**
     * @return list<array{title:string,description:string,items:list<array{label:string,meta:string,status:string}>}>
     */
    private function buildLists(User $user, string $roleScope): array
    {
        return match ($roleScope) {
            'empleado' => $this->buildEmployeeLists($user),
            'repartidor' => [[
                'title' => 'Mis pedidos',
                'description' => 'Solo se muestran ventas asignadas o entregadas por este repartidor.',
                'items' => Sale::query()
                    ->with('customer:id,name')
                    ->where('delivery_user_id', $user->id)
                    ->whereIn('status', [Sale::STATUS_ASSIGNED, Sale::STATUS_COMPLETED])
                    ->latest('delivery_date')
                    ->limit(6)
                    ->get()
                    ->map(fn (Sale $sale): array => [
                        'label' => $sale->folio.' · '.($sale->customer?->name ?? 'Sin cliente'),
                        'meta' => $sale->delivery_date?->format('d/m/Y H:i') ?? 'Sin fecha de entrega',
                        'status' => $sale->status,
                    ])->all(),
            ]],
            'planta' => [[
                'title' => 'Produccion reciente',
                'description' => 'Ordenes ligadas al trabajo de planta.',
                'items' => ProductionOrder::query()
                    ->with('product:id,name')
                    ->latest('scheduled_for')
                    ->limit(6)
                    ->get()
                    ->map(fn (ProductionOrder $productionOrder): array => [
                        'label' => $productionOrder->folio.' · '.$productionOrder->product->name,
                        'meta' => $productionOrder->scheduled_for?->format('d/m/Y H:i') ?? 'Sin programacion',
                        'status' => $productionOrder->status,
                    ])->all(),
            ]],
            default => [
                [
                    'title' => 'Asistencia de hoy',
                    'description' => 'Resumen rapido de empleados a tiempo, con retardo o falta.',
                    'items' => $this->attendanceOverviewItems(),
                ],
                [
                    'title' => 'Vista general',
                    'description' => 'Resumen rapido de catalogos y operacion.',
                    'items' => [
                        [
                            'label' => 'Productos activos: '.Product::query()->where('is_active', true)->count(),
                            'meta' => 'Catalogo comercial vigente',
                            'status' => 'active',
                        ],
                        [
                            'label' => 'Materias primas activas: '.RawMaterial::query()->where('is_active', true)->count(),
                            'meta' => 'Base de insumos disponible',
                            'status' => 'active',
                        ],
                        [
                            'label' => 'Clientes activos: '.Customer::query()->where('is_active', true)->count(),
                            'meta' => 'Cartera lista para vender',
                            'status' => 'active',
                        ],
                        [
                            'label' => 'Usuarios operativos: '.User::query()->count(),
                            'meta' => 'Personal con acceso al sistema',
                            'status' => 'active',
                        ],
                    ],
                ],
                [
                    'title' => 'Ultimos pedidos por reparto',
                    'description' => 'Entregas mas recientes para seguimiento rapido.',
                    'items' => Sale::query()
                        ->with(['customer:id,name', 'deliveryUser:id,name'])
                        ->where('sale_type', Sale::TYPE_DELIVERY)
                        ->latest('delivery_date')
                        ->limit(6)
                        ->get()
                        ->map(fn (Sale $sale): array => [
                            'label' => $sale->folio.' · '.($sale->customer?->name ?? 'Sin cliente'),
                            'meta' => ($sale->deliveryUser?->name ?? 'Sin repartidor').' · '.($sale->delivery_date?->format('d/m/Y H:i') ?? 'Sin fecha'),
                            'status' => $sale->status,
                        ])->all(),
                ],
            ],
        };
    }

    /**
     * @return list<array{title:string,value:string,description:string,tone:string}>
     */
    private function buildEmployeeCards(User $user): array
    {
        $attendance = $this->attendanceService->employeeDashboard($user);
        $today = $attendance['today'];
        $monthStart = CarbonImmutable::today()->startOfMonth();
        $monthEnd = CarbonImmutable::today();
        $monthlyRecords = $user->attendanceRecords()
            ->whereBetween('attendance_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        return [
            [
                'title' => 'Estado de hoy',
                'value' => $this->statusLabel($today['live_status']),
                'description' => 'Resultado actual de la asistencia del dia.',
                'tone' => 'nopal',
            ],
            [
                'title' => 'Entrada registrada',
                'value' => $today['check_in_at'] ? CarbonImmutable::parse($today['check_in_at'])->format('H:i') : '--:--',
                'description' => 'Hora exacta en que se capturo la entrada.',
                'tone' => 'maiz',
            ],
            [
                'title' => 'Retardos del mes',
                'value' => (string) $monthlyRecords->where('check_in_status', AttendanceRecord::STATUS_TARDY)->count(),
                'description' => 'Conteo acumulado dentro del mes actual.',
                'tone' => 'stone',
            ],
            [
                'title' => 'Dispositivos detectados',
                'value' => (string) $user->employeeDevices()->count(),
                'description' => 'Equipos desde los que se ha iniciado sesion o marcado.',
                'tone' => 'nopal',
            ],
        ];
    }

    /**
     * @return list<array{title:string,description:string,items:list<array{label:string,meta:string,status:string}>}>
     */
    private function buildEmployeeLists(User $user): array
    {
        $attendance = $this->attendanceService->employeeDashboard($user);
        $today = $attendance['today'];

        return [
            [
                'title' => 'Mi asistencia',
                'description' => 'Resumen inmediato del dia actual.',
                'items' => [
                    [
                        'label' => 'Entrada: '.($today['check_in_at'] ? CarbonImmutable::parse($today['check_in_at'])->format('d/m/Y H:i') : 'Pendiente'),
                        'meta' => 'Codigo de entrada: '.($today['entry_code'] ?? 'N/A'),
                        'status' => $today['check_in_status'],
                    ],
                    [
                        'label' => 'Salida: '.($today['check_out_at'] ? CarbonImmutable::parse($today['check_out_at'])->format('d/m/Y H:i') : 'Pendiente'),
                        'meta' => 'Codigo de salida: '.($today['exit_code'] ?? 'N/A'),
                        'status' => $today['check_out_status'],
                    ],
                ],
            ],
            [
                'title' => 'Mi dispositivo actual',
                'description' => 'Datos del equipo usados para validar la marca.',
                'items' => $attendance['current_device'] === null
                    ? []
                    : [[
                        'label' => $attendance['current_device']['device_name'],
                        'meta' => ($attendance['current_device']['browser_name'] ?? 'Navegador').' · '.($attendance['current_device']['platform_name'] ?? 'Plataforma').' · IP '.($attendance['current_device']['last_ip'] ?? 'N/A'),
                        'status' => 'active',
                    ]],
            ],
        ];
    }

    /**
     * @return list<array{label:string,meta:string,status:string}>
     */
    private function attendanceOverviewItems(): array
    {
        $employees = $this->employeeQuery()
            ->orderBy('name')
            ->get();

        $items = [];

        foreach ($employees as $employee) {
            $record = $this->attendanceService->ensureTodayRecord($employee);
            $status = match (true) {
                $record === null && CarbonImmutable::today()->lessThan(CarbonImmutable::parse(($employee->attendance_starts_at ?? $employee->created_at)->toDateString())) => AttendanceRecord::STATUS_NOT_STARTED,
                $record === null => AttendanceRecord::STATUS_OFF_DAY,
                $record->check_in_at !== null => $record->check_in_status,
                CarbonImmutable::now()->greaterThanOrEqualTo(CarbonImmutable::parse($record->absence_after_at)) => AttendanceRecord::STATUS_ABSENT,
                default => AttendanceRecord::STATUS_PENDING,
            };

            $items[] = [
                'label' => $employee->name,
                'meta' => $record?->check_in_at?->format('d/m/Y H:i') ?? 'Sin entrada registrada hoy',
                'status' => $status,
            ];
        }

        return array_slice($items, 0, 6);
    }

    private function attendanceCountForToday(string $status): int
    {
        return $this->employeeQuery()
            ->get()
            ->reduce(function (int $carry, User $employee) use ($status): int {
                $record = $this->attendanceService->ensureTodayRecord($employee);
                $liveStatus = match (true) {
                    $record === null && CarbonImmutable::today()->lessThan(CarbonImmutable::parse(($employee->attendance_starts_at ?? $employee->created_at)->toDateString())) => AttendanceRecord::STATUS_NOT_STARTED,
                    $record === null => AttendanceRecord::STATUS_OFF_DAY,
                    $record->check_in_at !== null => $record->check_in_status,
                    CarbonImmutable::now()->greaterThanOrEqualTo(CarbonImmutable::parse($record->absence_after_at)) => AttendanceRecord::STATUS_ABSENT,
                    default => AttendanceRecord::STATUS_PENDING,
                };

                return $carry + (int) ($liveStatus === $status);
            }, 0);
    }

    /**
     * @return Builder<User>
     */
    private function employeeQuery(): Builder
    {
        return User::query()->whereHas('roles', function (Builder $query): void {
            $query->where('name', 'empleado');
        });
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            AttendanceRecord::STATUS_PENDING => 'Pendiente',
            AttendanceRecord::STATUS_ON_TIME => 'A tiempo',
            AttendanceRecord::STATUS_TARDY => 'Retardo',
            AttendanceRecord::STATUS_ABSENT => 'Falta',
            AttendanceRecord::STATUS_COMPLETED => 'Completa',
            AttendanceRecord::STATUS_EARLY => 'Salida anticipada',
            AttendanceRecord::STATUS_OFF_DAY => 'Dia no laboral',
            AttendanceRecord::STATUS_NOT_STARTED => 'Fuera de periodo',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }
}
