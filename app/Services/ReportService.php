<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function __construct(private AttendanceService $attendanceService) {}

    /**
     * @return array<string, mixed>
     */
    public function build(?string $from = null, ?string $to = null): array
    {
        $startDate = $from
            ? CarbonImmutable::parse($from)->startOfDay()
            : CarbonImmutable::today()->subDays(29)->startOfDay();
        $endDate = $to
            ? CarbonImmutable::parse($to)->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        if ($endDate->lessThan($startDate)) {
            [$startDate, $endDate] = [$endDate->startOfDay(), $startDate->endOfDay()];
        }

        $attendance = $this->attendanceSection($startDate, $endDate);
        $sales = $this->salesSection($startDate, $endDate);
        $purchases = $this->purchasesSection($startDate, $endDate);
        $production = $this->productionSection($startDate, $endDate);
        $inventory = $this->inventorySection($startDate, $endDate);
        $finances = $this->financesSection($startDate, $endDate);

        return [
            'filters' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
            ],
            'overview' => [
                [
                    'label' => 'Ventas completadas',
                    'value' => $sales['summary']['completed_count'],
                    'tone' => 'emerald',
                    'detail' => $sales['summary']['revenue'],
                ],
                [
                    'label' => 'Compras recibidas',
                    'value' => $purchases['summary']['received_count'],
                    'tone' => 'amber',
                    'detail' => $purchases['summary']['spent'],
                ],
                [
                    'label' => 'Balance financiero',
                    'value' => $finances['summary']['balance'],
                    'tone' => ((float) $finances['summary']['balance']) >= 0 ? 'nopal' : 'red',
                    'detail' => "Ingresos {$finances['summary']['income']} · Egresos {$finances['summary']['expense']}",
                ],
                [
                    'label' => 'Produccion completada',
                    'value' => $production['summary']['completed_count'],
                    'tone' => 'sky',
                    'detail' => $production['summary']['produced_quantity'],
                ],
                [
                    'label' => 'Retardos / faltas',
                    'value' => (string) ($attendance['summary']['tardies'] + $attendance['summary']['absences']),
                    'tone' => 'stone',
                    'detail' => "{$attendance['summary']['tardies']} retardos · {$attendance['summary']['absences']} faltas",
                ],
            ],
            'attendance' => $attendance,
            'sales' => $sales,
            'purchases' => $purchases,
            'production' => $production,
            'inventory' => $inventory,
            'finances' => $finances,
            'details' => [
                'employees' => $this->employeeDetails($startDate, $endDate),
                'delivery_users' => $this->deliveryUserDetails($startDate, $endDate),
                'customers' => $this->customerDetails($startDate, $endDate),
                'products' => $this->productDetails($startDate, $endDate),
            ],
        ];
    }

    public function exportFileName(string $extension, ?string $from = null, ?string $to = null): string
    {
        $start = $from ?: CarbonImmutable::today()->subDays(29)->toDateString();
        $end = $to ?: CarbonImmutable::today()->toDateString();

        return "reportes-{$start}-{$end}.{$extension}";
    }

    /**
     * @return array<string, mixed>
     */
    private function attendanceSection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        /** @var Collection<int, User> $employees */
        $employees = User::query()
            ->role('empleado')
            ->orderBy('name')
            ->get();

        $rows = [];
        $attendances = 0;
        $tardies = 0;
        $absences = 0;
        $absenceEquivalents = 0;

        foreach ($employees as $employee) {
            /** @var array{employee:array<string,mixed>,summary:array{attendances:int,tardies:int,absences:int,absence_equivalents:int}} $detail */
            $detail = $this->attendanceService->employeeDetail(
                $employee,
                $startDate->toDateString(),
                $endDate->toDateString(),
            );

            $employeeSummary = $detail['summary'];

            $rows[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'attendance_starts_at' => $detail['employee']['attendance_starts_at'],
                'attendances' => $employeeSummary['attendances'],
                'tardies' => $employeeSummary['tardies'],
                'absences' => $employeeSummary['absences'],
                'absence_equivalents' => $employeeSummary['absence_equivalents'],
            ];

            $attendances += $employeeSummary['attendances'];
            $tardies += $employeeSummary['tardies'];
            $absences += $employeeSummary['absences'];
            $absenceEquivalents += $employeeSummary['absence_equivalents'];
        }

        usort($rows, function (array $left, array $right): int {
            $absenceComparison = $right['absences'] <=> $left['absences'];

            if ($absenceComparison !== 0) {
                return $absenceComparison;
            }

            $tardyComparison = $right['tardies'] <=> $left['tardies'];

            if ($tardyComparison !== 0) {
                return $tardyComparison;
            }

            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return [
            'summary' => [
                'employees_count' => (string) count($rows),
                'attendances' => (string) $attendances,
                'tardies' => (string) $tardies,
                'absences' => (string) $absences,
                'absence_equivalents' => (string) $absenceEquivalents,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function salesSection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $salesQuery = Sale::query()
            ->with(['customer:id,name', 'deliveryUser:id,name'])
            ->whereBetween('sale_date', [$startDate, $endDate]);

        $completedSales = (clone $salesQuery)->where('status', Sale::STATUS_COMPLETED);

        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(sale_items.line_total)'))
            ->limit(5)
            ->get([
                'products.name as label',
                DB::raw('SUM(sale_items.sold_quantity) as quantity'),
                DB::raw('SUM(sale_items.line_total) as amount'),
            ])
            ->map(fn ($row): array => [
                'label' => $row->label,
                'quantity' => number_format((float) $row->quantity, 3, '.', ''),
                'amount' => number_format((float) $row->amount, 2, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'completed_count' => (string) (clone $completedSales)->count(),
                'assigned_count' => (string) (clone $salesQuery)->where('status', Sale::STATUS_ASSIGNED)->count(),
                'direct_count' => (string) (clone $completedSales)->where('sale_type', Sale::TYPE_DIRECT)->count(),
                'delivery_count' => (string) (clone $completedSales)->where('sale_type', Sale::TYPE_DELIVERY)->count(),
                'revenue' => number_format((float) (clone $completedSales)->sum('total'), 2, '.', ''),
                'discount_total' => number_format((float) (clone $completedSales)->sum('discount_total'), 2, '.', ''),
            ],
            'top_products' => $topProducts,
            'recent' => (clone $salesQuery)
                ->latest('sale_date')
                ->limit(8)
                ->get()
                ->map(fn (Sale $sale): array => [
                    'id' => $sale->id,
                    'folio' => $sale->folio,
                    'customer' => $sale->customer?->name ?? 'Sin cliente',
                    'delivery_user' => $sale->deliveryUser?->name ?? 'Sin repartidor',
                    'status' => $sale->status,
                    'sale_type' => $sale->sale_type,
                    'total' => (string) $sale->total,
                    'sale_date' => $sale->sale_date?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function purchasesSection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $purchasesQuery = Purchase::query()
            ->with('supplier:id,name')
            ->withSum('items as items_total', 'total')
            ->whereBetween('purchased_at', [$startDate, $endDate]);

        $receivedPurchases = (clone $purchasesQuery)->where('status', Purchase::STATUS_RECEIVED);

        $topSuppliers = PurchaseItem::query()
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->whereBetween('purchases.purchased_at', [$startDate, $endDate])
            ->where('purchases.status', Purchase::STATUS_RECEIVED)
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc(DB::raw('SUM(purchase_items.total)'))
            ->limit(5)
            ->get([
                'suppliers.name as label',
                DB::raw('COUNT(DISTINCT purchases.id) as purchases_count'),
                DB::raw('SUM(purchase_items.total) as amount'),
            ])
            ->map(fn ($row): array => [
                'label' => $row->label,
                'purchases_count' => (string) $row->purchases_count,
                'amount' => number_format((float) $row->amount, 2, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'received_count' => (string) (clone $receivedPurchases)->count(),
                'draft_count' => (string) (clone $purchasesQuery)->where('status', Purchase::STATUS_DRAFT)->count(),
                'cancelled_count' => (string) (clone $purchasesQuery)->where('status', Purchase::STATUS_CANCELLED)->count(),
                'spent' => number_format((float) PurchaseItem::query()
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereBetween('purchases.purchased_at', [$startDate, $endDate])
                    ->where('purchases.status', Purchase::STATUS_RECEIVED)
                    ->sum('purchase_items.total'), 2, '.', ''),
            ],
            'top_suppliers' => $topSuppliers,
            'recent' => (clone $purchasesQuery)
                ->latest('purchased_at')
                ->limit(8)
                ->get()
                ->map(fn (Purchase $purchase): array => [
                    'id' => $purchase->id,
                    'folio' => $purchase->folio,
                    'supplier' => $purchase->supplier?->name ?? 'Sin proveedor',
                    'status' => $purchase->status,
                    'total' => number_format((float) ($purchase->items_total ?? 0), 2, '.', ''),
                    'purchased_at' => $purchase->purchased_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productionSection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $ordersQuery = ProductionOrder::query()
            ->with(['product:id,name', 'unit:id,name,code'])
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->whereBetween('scheduled_for', [$startDate, $endDate])
                    ->orWhereBetween('started_at', [$startDate, $endDate])
                    ->orWhereBetween('finished_at', [$startDate, $endDate]);
            });

        $completedOrders = (clone $ordersQuery)->where('status', ProductionOrder::STATUS_COMPLETED);

        $topProducts = ProductionOrder::query()
            ->join('products', 'products.id', '=', 'production_orders.product_id')
            ->where('production_orders.status', ProductionOrder::STATUS_COMPLETED)
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->whereBetween('production_orders.scheduled_for', [$startDate, $endDate])
                    ->orWhereBetween('production_orders.started_at', [$startDate, $endDate])
                    ->orWhereBetween('production_orders.finished_at', [$startDate, $endDate]);
            })
            ->groupBy('products.id', 'products.name')
            ->orderByDesc(DB::raw('SUM(production_orders.produced_quantity)'))
            ->limit(5)
            ->get([
                'products.name as label',
                DB::raw('COUNT(production_orders.id) as orders_count'),
                DB::raw('SUM(production_orders.produced_quantity) as produced_quantity'),
            ])
            ->map(fn ($row): array => [
                'label' => $row->label,
                'orders_count' => (string) $row->orders_count,
                'produced_quantity' => number_format((float) $row->produced_quantity, 3, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'completed_count' => (string) (clone $completedOrders)->count(),
                'in_progress_count' => (string) (clone $ordersQuery)->where('status', ProductionOrder::STATUS_IN_PROGRESS)->count(),
                'planned_quantity' => number_format((float) (clone $ordersQuery)->sum('planned_quantity'), 3, '.', ''),
                'produced_quantity' => number_format((float) (clone $completedOrders)->sum('produced_quantity'), 3, '.', ''),
            ],
            'top_products' => $topProducts,
            'recent' => (clone $ordersQuery)
                ->latest('scheduled_for')
                ->limit(8)
                ->get()
                ->map(fn (ProductionOrder $order): array => [
                    'id' => $order->id,
                    'folio' => $order->folio,
                    'product' => $order->product?->name ?? 'Sin producto',
                    'status' => $order->status,
                    'planned_quantity' => (string) $order->planned_quantity,
                    'produced_quantity' => (string) $order->produced_quantity,
                    'unit' => $order->unit?->code ?? $order->unit?->name ?? '',
                    'scheduled_for' => $order->scheduled_for?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function inventorySection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $movementsQuery = InventoryMovement::query()
            ->with(['warehouse:id,name', 'rawMaterial:id,name', 'product:id,name'])
            ->whereBetween('moved_at', [$startDate, $endDate]);

        $typeBreakdown = InventoryMovement::query()
            ->whereBetween('moved_at', [$startDate, $endDate])
            ->groupBy('movement_type')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                'movement_type',
                DB::raw('COUNT(*) as movements_count'),
                DB::raw('SUM(quantity) as quantity_total'),
            ])
            ->map(fn (InventoryMovement $movement): array => [
                'label' => $movement->movement_type,
                'movements_count' => (string) $movement->movements_count,
                'quantity_total' => number_format((float) $movement->quantity_total, 3, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'movements_count' => (string) (clone $movementsQuery)->count(),
                'entries_quantity' => number_format((float) (clone $movementsQuery)
                    ->where('direction', InventoryMovement::DIRECTION_IN)
                    ->sum('quantity'), 3, '.', ''),
                'exits_quantity' => number_format((float) (clone $movementsQuery)
                    ->where('direction', InventoryMovement::DIRECTION_OUT)
                    ->sum('quantity'), 3, '.', ''),
            ],
            'type_breakdown' => $typeBreakdown,
            'recent' => (clone $movementsQuery)
                ->latest('moved_at')
                ->limit(8)
                ->get()
                ->map(fn (InventoryMovement $movement): array => [
                    'id' => $movement->id,
                    'movement_type' => $movement->movement_type,
                    'direction' => $movement->direction,
                    'warehouse' => $movement->warehouse?->name ?? 'Sin almacen',
                    'item' => $movement->item_type === InventoryMovement::ITEM_TYPE_PRODUCT
                        ? ($movement->product?->name ?? 'Producto')
                        : ($movement->rawMaterial?->name ?? 'Materia prima'),
                    'quantity' => (string) $movement->quantity,
                    'moved_at' => $movement->moved_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financesSection(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $transactionsQuery = FinanceTransaction::query()
            ->with('creator:id,name')
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        $postedTransactions = (clone $transactionsQuery)
            ->where('status', FinanceTransaction::STATUS_POSTED)
            ->where('affects_balance', true);

        $income = (float) (clone $postedTransactions)
            ->where('direction', FinanceTransaction::DIRECTION_IN)
            ->sum('amount');
        $expense = (float) (clone $postedTransactions)
            ->where('direction', FinanceTransaction::DIRECTION_OUT)
            ->sum('amount');

        $sourceBreakdown = FinanceTransaction::query()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->groupBy('source')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->get([
                'source',
                DB::raw('COUNT(*) as transactions_count'),
                DB::raw('SUM(amount) as amount_total'),
            ])
            ->map(fn (FinanceTransaction $transaction): array => [
                'label' => $transaction->source,
                'transactions_count' => (string) $transaction->transactions_count,
                'amount_total' => number_format((float) $transaction->amount_total, 2, '.', ''),
            ])
            ->all();

        return [
            'summary' => [
                'income' => number_format($income, 2, '.', ''),
                'expense' => number_format($expense, 2, '.', ''),
                'balance' => number_format($income - $expense, 2, '.', ''),
                'debts' => number_format((float) (clone $transactionsQuery)
                    ->where('transaction_type', FinanceTransaction::TYPE_DEBT)
                    ->where('status', FinanceTransaction::STATUS_PENDING)
                    ->sum('amount'), 2, '.', ''),
            ],
            'source_breakdown' => $sourceBreakdown,
            'recent' => (clone $transactionsQuery)
                ->latest('occurred_at')
                ->limit(8)
                ->get()
                ->map(fn (FinanceTransaction $transaction): array => [
                    'id' => $transaction->id,
                    'folio' => $transaction->folio,
                    'concept' => $transaction->concept,
                    'transaction_type' => $transaction->transaction_type,
                    'direction' => $transaction->direction,
                    'source' => $transaction->source,
                    'amount' => (string) $transaction->amount,
                    'status' => $transaction->status,
                    'occurred_at' => $transaction->occurred_at?->toDateTimeString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function employeeDetails(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        /** @var Collection<int, User> $employees */
        $employees = User::query()
            ->role('empleado')
            ->withCount('employeeDevices')
            ->orderBy('name')
            ->get();

        return $employees->map(function (User $employee) use ($startDate, $endDate): array {
            /** @var array{employee:array<string,mixed>,summary:array{attendances:int,tardies:int,absences:int,absence_equivalents:int}} $detail */
            $detail = $this->attendanceService->employeeDetail(
                $employee,
                $startDate->toDateString(),
                $endDate->toDateString(),
            );

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'attendance_starts_at' => $detail['employee']['attendance_starts_at'],
                'attendances' => $detail['summary']['attendances'],
                'tardies' => $detail['summary']['tardies'],
                'absences' => $detail['summary']['absences'],
                'absence_equivalents' => $detail['summary']['absence_equivalents'],
                'devices_count' => $employee->employee_devices_count,
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function deliveryUserDetails(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        /** @var Collection<int, User> $deliveryUsers */
        $deliveryUsers = User::query()
            ->role('repartidor')
            ->orderBy('name')
            ->get();

        return $deliveryUsers->map(function (User $deliveryUser) use ($startDate, $endDate): array {
            $salesQuery = Sale::query()
                ->where('sale_type', Sale::TYPE_DELIVERY)
                ->where('delivery_user_id', $deliveryUser->id)
                ->whereBetween('sale_date', [$startDate, $endDate]);

            return [
                'id' => $deliveryUser->id,
                'name' => $deliveryUser->name,
                'username' => $deliveryUser->username,
                'assigned_count' => (string) (clone $salesQuery)->where('status', Sale::STATUS_ASSIGNED)->count(),
                'completed_count' => (string) (clone $salesQuery)->where('status', Sale::STATUS_COMPLETED)->count(),
                'cancelled_count' => (string) (clone $salesQuery)->where('status', Sale::STATUS_CANCELLED)->count(),
                'total' => number_format((float) (clone $salesQuery)
                    ->where('status', Sale::STATUS_COMPLETED)
                    ->sum('total'), 2, '.', ''),
            ];
        })->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function customerDetails(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        return Sale::query()
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->whereBetween('sales.sale_date', [$startDate, $endDate])
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc(DB::raw('SUM(sales.total)'))
            ->get([
                'customers.id',
                'customers.name',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.discount_total) as discount_total'),
                DB::raw('SUM(sales.total) as total'),
                DB::raw('MAX(sales.sale_date) as last_sale_at'),
            ])
            ->map(fn ($row): array => [
                'id' => $row->id,
                'name' => $row->name,
                'sales_count' => (string) $row->sales_count,
                'discount_total' => number_format((float) $row->discount_total, 2, '.', ''),
                'total' => number_format((float) $row->total, 2, '.', ''),
                'last_sale_at' => $row->last_sale_at,
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productDetails(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        /** @var Collection<int, Product> $products */
        $products = Product::query()->orderBy('name')->get();

        return $products->map(function (Product $product) use ($startDate, $endDate) {
            $salesItemQuery = SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $product->id)
                ->where('sales.status', Sale::STATUS_COMPLETED)
                ->whereBetween('sales.sale_date', [$startDate, $endDate]);

            $purchaseItemQuery = PurchaseItem::query()
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchase_items.item_type', PurchaseItem::ITEM_TYPE_PRODUCT)
                ->where('purchase_items.item_id', $product->id)
                ->where('purchases.status', Purchase::STATUS_RECEIVED)
                ->whereBetween('purchases.purchased_at', [$startDate, $endDate]);

            $productionOrderQuery = ProductionOrder::query()
                ->where('product_id', $product->id)
                ->where('status', ProductionOrder::STATUS_COMPLETED)
                ->where(function ($query) use ($startDate, $endDate): void {
                    $query
                        ->whereBetween('scheduled_for', [$startDate, $endDate])
                        ->orWhereBetween('started_at', [$startDate, $endDate])
                        ->orWhereBetween('finished_at', [$startDate, $endDate]);
                });

            $soldQuantity = (float) (clone $salesItemQuery)->sum('sale_items.sold_quantity');
            $salesTotal = (float) (clone $salesItemQuery)->sum('sale_items.line_total');
            $purchasedQuantity = (float) (clone $purchaseItemQuery)->sum('purchase_items.quantity');
            $purchaseTotal = (float) (clone $purchaseItemQuery)->sum('purchase_items.total');
            $producedQuantity = (float) (clone $productionOrderQuery)->sum('produced_quantity');

            if ($soldQuantity === 0.0 && $salesTotal === 0.0 && $purchasedQuantity === 0.0 && $purchaseTotal === 0.0 && $producedQuantity === 0.0) {
                return null;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sold_quantity' => number_format($soldQuantity, 3, '.', ''),
                'sales_total' => number_format($salesTotal, 2, '.', ''),
                'purchased_quantity' => number_format($purchasedQuantity, 3, '.', ''),
                'purchase_total' => number_format($purchaseTotal, 2, '.', ''),
                'produced_quantity' => number_format($producedQuantity, 3, '.', ''),
            ];
        })->filter()->values()->all();
    }
}
