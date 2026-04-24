import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    attendanceStatusAccentClass,
    attendanceStatusBadgeClass,
    formatAttendanceStatus,
} from '@/lib/attendance-display';
import { index, show } from '@/routes/employees';
import type { EmployeeRecord } from '@/types';

type PaginatedEmployees = {
    data: EmployeeRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const formatDateTime = (value: string | null): string => {
    if (value === null) {
        return 'Sin registro';
    }

    return new Date(value).toLocaleString('es-MX');
};

export default function EmployeesIndex({
    employees,
    filters,
}: {
    employees: PaginatedEmployees;
    filters: { search?: string };
}) {
    return (
        <>
            <Head title="Empleados" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="space-y-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Empleados"
                        description="Consulta asistencia diaria, retardos, faltas equivalentes y dispositivos registrados."
                    />

                    <form
                        className="flex flex-col gap-3 md:flex-row"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);
                            const search = String(formData.get('search') ?? '');

                            router.get(index.url(), { search }, { preserveState: true, replace: true });
                        }}
                    >
                        <Input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Buscar por nombre, usuario o email"
                            className="border-stone-200 bg-white md:max-w-md"
                        />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">
                                Buscar
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => router.get(index.url(), {}, { preserveState: true, replace: true })}
                            >
                                Limpiar
                            </Button>
                        </div>
                    </form>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Empleado</th>
                                    <th className="px-6 py-4 font-medium">Hoy</th>
                                    <th className="px-6 py-4 font-medium">Entrada</th>
                                    <th className="px-6 py-4 font-medium">Salida</th>
                                    <th className="px-6 py-4 font-medium">Acumulado</th>
                                    <th className="px-6 py-4 font-medium">Dispositivos</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {employees.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-8 text-center text-stone-500">
                                            No hay empleados para los filtros seleccionados.
                                        </td>
                                    </tr>
                                ) : (
                                    employees.data.map((employee) => (
                                        <tr key={employee.id} className="border-t border-stone-200">
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-stone-900">{employee.name}</div>
                                                <div className="text-xs text-stone-500">
                                                    @{employee.username} · {employee.email}
                                                </div>
                                                <div className="text-xs text-stone-400">
                                                    Desde asistencia: {employee.attendance_starts_at ?? 'Sin fecha'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge
                                                    variant="outline"
                                                    className={attendanceStatusBadgeClass(employee.today_status)}
                                                >
                                                    {formatAttendanceStatus(employee.today_status)}
                                                </Badge>
                                                {employee.late_minutes > 0 && (
                                                    <div className="mt-2 text-xs text-amber-700">
                                                        {employee.late_minutes} min de atraso
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                {formatDateTime(employee.check_in_at)}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                {formatDateTime(employee.check_out_at)}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <div>Retardos: {employee.tardies}</div>
                                                <div>Faltas: {employee.absences}</div>
                                                <div className={attendanceStatusAccentClass(employee.absence_equivalents > 0 ? 'absent' : 'on_time')}>
                                                    Equiv. falta: {employee.absence_equivalents}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                {employee.devices_count}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <Button asChild variant="outline">
                                                        <Link href={show(employee.id)}>Ver detalle</Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-6 py-4">
                        <p className="text-sm text-stone-600">{employees.data.length} empleado(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {employees.links.map((link) => (
                                <Button
                                    key={link.label}
                                    variant={link.active ? 'default' : 'outline'}
                                    disabled={link.url === null}
                                    onClick={() => {
                                        if (link.url) {
                                            router.visit(link.url, { preserveScroll: true, preserveState: true });
                                        }
                                    }}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

EmployeesIndex.layout = {
    breadcrumbs: [{ title: 'Empleados', href: index() }],
};
