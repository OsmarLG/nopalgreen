import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatMoney } from '@/lib/money';
import { exportExcel, exportPdf, index } from '@/routes/reports';

type OverviewCard = {
    label: string;
    value: string;
    tone: 'emerald' | 'amber' | 'nopal' | 'red' | 'sky' | 'stone';
    detail: string;
};

type AttendanceRow = {
    id: number;
    name: string;
    username: string;
    attendance_starts_at: string | null;
    attendances: number;
    tardies: number;
    absences: number;
    absence_equivalents: number;
};

type LabeledAmountRow = {
    label: string;
    amount?: string;
    amount_total?: string;
    quantity?: string;
    quantity_total?: string;
    purchases_count?: string;
    orders_count?: string;
    transactions_count?: string;
    movements_count?: string;
    produced_quantity?: string;
};

type ReportPayload = {
    filters: { from: string; to: string };
    overview: OverviewCard[];
    attendance: {
        summary: {
            employees_count: string;
            attendances: string;
            tardies: string;
            absences: string;
            absence_equivalents: string;
        };
        rows: AttendanceRow[];
    };
    sales: {
        summary: {
            completed_count: string;
            assigned_count: string;
            direct_count: string;
            delivery_count: string;
            revenue: string;
            discount_total: string;
        };
        top_products: Array<{ label: string; quantity: string; amount: string }>;
        recent: Array<{
            id: number;
            folio: string;
            customer: string;
            delivery_user: string;
            status: string;
            sale_type: string;
            total: string;
            sale_date: string | null;
        }>;
    };
    purchases: {
        summary: {
            received_count: string;
            draft_count: string;
            cancelled_count: string;
            spent: string;
        };
        top_suppliers: Array<LabeledAmountRow>;
        recent: Array<{
            id: number;
            folio: string;
            supplier: string;
            status: string;
            total: string;
            purchased_at: string | null;
        }>;
    };
    production: {
        summary: {
            completed_count: string;
            in_progress_count: string;
            planned_quantity: string;
            produced_quantity: string;
        };
        top_products: Array<LabeledAmountRow>;
        recent: Array<{
            id: number;
            folio: string;
            product: string;
            status: string;
            planned_quantity: string;
            produced_quantity: string;
            unit: string;
            scheduled_for: string | null;
        }>;
    };
    inventory: {
        summary: {
            movements_count: string;
            entries_quantity: string;
            exits_quantity: string;
        };
        type_breakdown: Array<LabeledAmountRow>;
        recent: Array<{
            id: number;
            movement_type: string;
            direction: string;
            warehouse: string;
            item: string;
            quantity: string;
            moved_at: string | null;
        }>;
    };
    finances: {
        summary: {
            income: string;
            expense: string;
            balance: string;
            debts: string;
        };
        source_breakdown: Array<LabeledAmountRow>;
        recent: Array<{
            id: number;
            folio: string;
            concept: string;
            transaction_type: string;
            direction: string;
            source: string;
            amount: string;
            status: string;
            occurred_at: string | null;
        }>;
    };
    details: {
        employees: Array<{
            id: number;
            name: string;
            username: string;
            attendance_starts_at: string | null;
            attendances: number;
            tardies: number;
            absences: number;
            absence_equivalents: number;
            devices_count: number;
        }>;
        delivery_users: Array<{
            id: number;
            name: string;
            username: string;
            assigned_count: string;
            completed_count: string;
            cancelled_count: string;
            total: string;
        }>;
        customers: Array<{
            id: number;
            name: string;
            sales_count: string;
            discount_total: string;
            total: string;
            last_sale_at: string | null;
        }>;
        products: Array<{
            id: number;
            name: string;
            sold_quantity: string;
            sales_total: string;
            purchased_quantity: string;
            purchase_total: string;
            produced_quantity: string;
        }>;
    };
};

const toneClasses: Record<OverviewCard['tone'], string> = {
    emerald: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    amber: 'border-amber-200 bg-amber-50 text-amber-900',
    nopal: 'border-nopal-200 bg-nopal-50 text-nopal-900',
    red: 'border-red-200 bg-red-50 text-red-900',
    sky: 'border-sky-200 bg-sky-50 text-sky-900',
    stone: 'border-stone-200 bg-stone-50 text-stone-900',
};

function formatLabel(value: string): string {
    return value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Sin fecha';
    }

    return new Date(value).toLocaleString('es-MX');
}

function MetricCard({ label, value, money = false }: { label: string; value: string; money?: boolean }) {
    return (
        <div className="rounded-[1.5rem] border border-stone-200 bg-white p-4">
            <p className="text-sm text-stone-500">{label}</p>
            <p className="mt-2 text-2xl font-semibold text-stone-900">{money ? formatMoney(value) : value}</p>
        </div>
    );
}

export default function ReportsIndex(report: ReportPayload) {
    const { filters, overview, attendance, sales, purchases, production, inventory, finances, details } = report;

    return (
        <>
            <Head title="Reportes" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading title="Reportes" description="Consolida asistencia, ventas, compras, produccion, inventario y finanzas en un solo corte." />
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href={exportExcel({ query: { from: filters.from, to: filters.to } })}>Excel</Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={exportPdf({ query: { from: filters.from, to: filters.to } })} target="_blank">PDF</Link>
                            </Button>
                        </div>
                    </div>

                    <form
                        className="mt-6 grid gap-3 md:grid-cols-[220px_220px_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);

                            router.get(
                                index.url(),
                                {
                                    from: String(formData.get('from') ?? ''),
                                    to: String(formData.get('to') ?? ''),
                                },
                                { preserveState: true, replace: true },
                            );
                        }}
                    >
                        <Input
                            name="from"
                            type="date"
                            defaultValue={filters.from}
                            className="border-stone-200 bg-white text-stone-900 [color-scheme:light]"
                        />
                        <Input
                            name="to"
                            type="date"
                            defaultValue={filters.to}
                            className="border-stone-200 bg-white text-stone-900 [color-scheme:light]"
                        />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">Aplicar</Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => router.get(index.url(), {}, { preserveState: true, replace: true })}
                            >
                                Limpiar
                            </Button>
                        </div>
                    </form>

                    <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        {overview.map((card) => (
                            <div key={card.label} className={`rounded-[1.75rem] border p-5 ${toneClasses[card.tone]}`}>
                                <p className="text-sm opacity-80">{card.label}</p>
                                <p className="mt-2 text-3xl font-semibold">
                                    {card.label.toLowerCase().includes('balance') ? formatMoney(card.value) : card.value}
                                </p>
                                <p className="mt-2 text-sm opacity-80">
                                    {card.detail.includes('.') || card.detail.includes('$') ? card.detail : card.detail}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Asistencia" description="Resumen de empleados, asistencias, retardos y faltas." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <MetricCard label="Empleados" value={attendance.summary.employees_count} />
                            <MetricCard label="Asistencias" value={attendance.summary.attendances} />
                            <MetricCard label="Retardos" value={attendance.summary.tardies} />
                            <MetricCard label="Faltas" value={attendance.summary.absences} />
                            <MetricCard label="Faltas por retardos" value={attendance.summary.absence_equivalents} />
                        </div>
                        <div className="mt-5 overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Empleado</th>
                                        <th className="px-4 py-3 font-medium">Inicio</th>
                                        <th className="px-4 py-3 font-medium">Asist.</th>
                                        <th className="px-4 py-3 font-medium">Ret.</th>
                                        <th className="px-4 py-3 font-medium">Faltas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {attendance.rows.map((row) => (
                                        <tr key={row.id} className="border-t border-stone-200">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-stone-900">{row.name}</div>
                                                <div className="text-xs text-stone-500">{row.username}</div>
                                            </td>
                                            <td className="px-4 py-3 text-stone-600">{row.attendance_starts_at ?? 'Sin fecha'}</td>
                                            <td className="px-4 py-3 text-stone-900">{row.attendances}</td>
                                            <td className="px-4 py-3 text-amber-700">{row.tardies}</td>
                                            <td className="px-4 py-3 text-red-700">{row.absences}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Ventas" description="Ventas completadas, descuentos y productos mas vendidos." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <MetricCard label="Completadas" value={sales.summary.completed_count} />
                            <MetricCard label="Asignadas" value={sales.summary.assigned_count} />
                            <MetricCard label="Directas" value={sales.summary.direct_count} />
                            <MetricCard label="Entrega" value={sales.summary.delivery_count} />
                            <MetricCard label="Ingresos" value={sales.summary.revenue} money />
                            <MetricCard label="Descuentos" value={sales.summary.discount_total} money />
                        </div>
                        <div className="mt-5 grid gap-4 xl:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Top productos</h3>
                                <div className="mt-3 space-y-3">
                                    {sales.top_products.map((row) => (
                                        <div key={row.label} className="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                            <div className="font-medium text-stone-900">{row.label}</div>
                                            <div className="mt-1 text-xs text-stone-500">{row.quantity} vendida(s)</div>
                                            <div className="mt-2 text-sm font-medium text-stone-800">{formatMoney(row.amount)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Ventas recientes</h3>
                                <div className="mt-3 space-y-3">
                                    {sales.recent.map((row) => (
                                        <div key={row.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="font-medium text-stone-900">{row.folio}</span>
                                                <span className="text-xs text-stone-500">{formatMoney(row.total)}</span>
                                            </div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {row.customer} · {formatLabel(row.sale_type)} · {formatLabel(row.status)}
                                            </div>
                                            <div className="mt-1 text-xs text-stone-400">{formatDateTime(row.sale_date)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Compras" description="Compras recibidas, gasto total y proveedores con mayor monto." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard label="Recibidas" value={purchases.summary.received_count} />
                            <MetricCard label="Borrador" value={purchases.summary.draft_count} />
                            <MetricCard label="Canceladas" value={purchases.summary.cancelled_count} />
                            <MetricCard label="Gasto" value={purchases.summary.spent} money />
                        </div>
                        <div className="mt-5 grid gap-4 xl:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Top proveedores</h3>
                                <div className="mt-3 space-y-3">
                                    {purchases.top_suppliers.map((row) => (
                                        <div key={row.label} className="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                            <div className="font-medium text-stone-900">{row.label}</div>
                                            <div className="mt-1 text-xs text-stone-500">{row.purchases_count} compra(s)</div>
                                            <div className="mt-2 text-sm font-medium text-stone-800">{formatMoney(row.amount ?? '0')}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Compras recientes</h3>
                                <div className="mt-3 space-y-3">
                                    {purchases.recent.map((row) => (
                                        <div key={row.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="font-medium text-stone-900">{row.folio}</span>
                                                <span className="text-xs text-stone-500">{formatMoney(row.total)}</span>
                                            </div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {row.supplier} · {formatLabel(row.status)}
                                            </div>
                                            <div className="mt-1 text-xs text-stone-400">{formatDateTime(row.purchased_at)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Produccion" description="Ordenes completadas, cantidad planeada y cantidad producida." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard label="Completadas" value={production.summary.completed_count} />
                            <MetricCard label="En proceso" value={production.summary.in_progress_count} />
                            <MetricCard label="Planeada" value={production.summary.planned_quantity} />
                            <MetricCard label="Producida" value={production.summary.produced_quantity} />
                        </div>
                        <div className="mt-5 grid gap-4 xl:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Top productos</h3>
                                <div className="mt-3 space-y-3">
                                    {production.top_products.map((row) => (
                                        <div key={row.label} className="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                            <div className="font-medium text-stone-900">{row.label}</div>
                                            <div className="mt-1 text-xs text-stone-500">{row.orders_count} orden(es)</div>
                                            <div className="mt-2 text-sm font-medium text-stone-800">{row.produced_quantity}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Ordenes recientes</h3>
                                <div className="mt-3 space-y-3">
                                    {production.recent.map((row) => (
                                        <div key={row.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="font-medium text-stone-900">{row.folio}</span>
                                                <span className="text-xs text-stone-500">{formatLabel(row.status)}</span>
                                            </div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {row.product} · {row.produced_quantity} / {row.planned_quantity} {row.unit}
                                            </div>
                                            <div className="mt-1 text-xs text-stone-400">{formatDateTime(row.scheduled_for)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Inventario" description="Entradas, salidas y tipos de movimiento en el periodo." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-3">
                            <MetricCard label="Movimientos" value={inventory.summary.movements_count} />
                            <MetricCard label="Entradas" value={inventory.summary.entries_quantity} />
                            <MetricCard label="Salidas" value={inventory.summary.exits_quantity} />
                        </div>
                        <div className="mt-5 grid gap-4 xl:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Tipos de movimiento</h3>
                                <div className="mt-3 space-y-3">
                                    {inventory.type_breakdown.map((row) => (
                                        <div key={row.label} className="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                            <div className="font-medium text-stone-900">{formatLabel(row.label)}</div>
                                            <div className="mt-1 text-xs text-stone-500">{row.movements_count} movimiento(s)</div>
                                            <div className="mt-2 text-sm font-medium text-stone-800">{row.quantity_total}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Movimientos recientes</h3>
                                <div className="mt-3 space-y-3">
                                    {inventory.recent.map((row) => (
                                        <div key={row.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="font-medium text-stone-900">{row.item}</span>
                                                <span className="text-xs text-stone-500">{row.quantity}</span>
                                            </div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {formatLabel(row.movement_type)} · {formatLabel(row.direction)} · {row.warehouse}
                                            </div>
                                            <div className="mt-1 text-xs text-stone-400">{formatDateTime(row.moved_at)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Finanzas" description="Ingresos, egresos, balance, deudas y fuentes de movimiento." />
                        <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <MetricCard label="Ingresos" value={finances.summary.income} money />
                            <MetricCard label="Egresos" value={finances.summary.expense} money />
                            <MetricCard label="Balance" value={finances.summary.balance} money />
                            <MetricCard label="Deudas" value={finances.summary.debts} money />
                        </div>
                        <div className="mt-5 grid gap-4 xl:grid-cols-2">
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Por fuente</h3>
                                <div className="mt-3 space-y-3">
                                    {finances.source_breakdown.map((row) => (
                                        <div key={row.label} className="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                                            <div className="font-medium text-stone-900">{formatLabel(row.label)}</div>
                                            <div className="mt-1 text-xs text-stone-500">{row.transactions_count} movimiento(s)</div>
                                            <div className="mt-2 text-sm font-medium text-stone-800">{formatMoney(row.amount_total ?? '0')}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <h3 className="text-sm font-semibold text-stone-900">Movimientos recientes</h3>
                                <div className="mt-3 space-y-3">
                                    {finances.recent.map((row) => (
                                        <div key={row.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                            <div className="flex items-center justify-between gap-3">
                                                <span className="font-medium text-stone-900">{row.folio}</span>
                                                <span className="text-xs text-stone-500">{formatMoney(row.amount)}</span>
                                            </div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {row.concept} · {formatLabel(row.source)} · {formatLabel(row.status)}
                                            </div>
                                            <div className="mt-1 text-xs text-stone-400">{formatDateTime(row.occurred_at)}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Detalle por empleado" description="Asistencia individual, retardos, faltas equivalentes y dispositivos." />
                        <div className="mt-5 overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Empleado</th>
                                        <th className="px-4 py-3 font-medium">Inicio</th>
                                        <th className="px-4 py-3 font-medium">Asist.</th>
                                        <th className="px-4 py-3 font-medium">Ret.</th>
                                        <th className="px-4 py-3 font-medium">Faltas</th>
                                        <th className="px-4 py-3 font-medium">Disp.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {details.employees.map((row) => (
                                        <tr key={row.id} className="border-t border-stone-200">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-stone-900">{row.name}</div>
                                                <div className="text-xs text-stone-500">{row.username}</div>
                                            </td>
                                            <td className="px-4 py-3 text-stone-600">{row.attendance_starts_at ?? 'Sin fecha'}</td>
                                            <td className="px-4 py-3">{row.attendances}</td>
                                            <td className="px-4 py-3 text-amber-700">{row.tardies}</td>
                                            <td className="px-4 py-3 text-red-700">{row.absences}</td>
                                            <td className="px-4 py-3 text-stone-600">{row.devices_count}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Detalle por repartidor" description="Ventas por entrega asignadas, completadas y total entregado." />
                        <div className="mt-5 overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Repartidor</th>
                                        <th className="px-4 py-3 font-medium">Asignadas</th>
                                        <th className="px-4 py-3 font-medium">Completadas</th>
                                        <th className="px-4 py-3 font-medium">Canceladas</th>
                                        <th className="px-4 py-3 font-medium">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {details.delivery_users.map((row) => (
                                        <tr key={row.id} className="border-t border-stone-200">
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-stone-900">{row.name}</div>
                                                <div className="text-xs text-stone-500">{row.username}</div>
                                            </td>
                                            <td className="px-4 py-3">{row.assigned_count}</td>
                                            <td className="px-4 py-3">{row.completed_count}</td>
                                            <td className="px-4 py-3">{row.cancelled_count}</td>
                                            <td className="px-4 py-3 font-medium text-stone-900">{formatMoney(row.total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Detalle por cliente" description="Frecuencia de compra, descuentos y total vendido por cliente." />
                        <div className="mt-5 overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Cliente</th>
                                        <th className="px-4 py-3 font-medium">Ventas</th>
                                        <th className="px-4 py-3 font-medium">Descuento</th>
                                        <th className="px-4 py-3 font-medium">Total</th>
                                        <th className="px-4 py-3 font-medium">Ultima venta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {details.customers.map((row) => (
                                        <tr key={row.id} className="border-t border-stone-200">
                                            <td className="px-4 py-3 font-medium text-stone-900">{row.name}</td>
                                            <td className="px-4 py-3">{row.sales_count}</td>
                                            <td className="px-4 py-3 text-amber-700">{formatMoney(row.discount_total)}</td>
                                            <td className="px-4 py-3 font-medium text-stone-900">{formatMoney(row.total)}</td>
                                            <td className="px-4 py-3 text-stone-500">{formatDateTime(row.last_sale_at)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <Heading title="Detalle por producto" description="Venta, compra y produccion por producto dentro del periodo." />
                        <div className="mt-5 overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">Producto</th>
                                        <th className="px-4 py-3 font-medium">Vendido</th>
                                        <th className="px-4 py-3 font-medium">Venta</th>
                                        <th className="px-4 py-3 font-medium">Comprado</th>
                                        <th className="px-4 py-3 font-medium">Compra</th>
                                        <th className="px-4 py-3 font-medium">Producido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {details.products.map((row) => (
                                        <tr key={row.id} className="border-t border-stone-200">
                                            <td className="px-4 py-3 font-medium text-stone-900">{row.name}</td>
                                            <td className="px-4 py-3">{row.sold_quantity}</td>
                                            <td className="px-4 py-3 font-medium text-stone-900">{formatMoney(row.sales_total)}</td>
                                            <td className="px-4 py-3">{row.purchased_quantity}</td>
                                            <td className="px-4 py-3 font-medium text-stone-900">{formatMoney(row.purchase_total)}</td>
                                            <td className="px-4 py-3">{row.produced_quantity}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Reportes', href: index() }],
};
