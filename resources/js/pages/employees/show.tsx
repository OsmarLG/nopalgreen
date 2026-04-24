import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    attendanceStatusBadgeClass,
    formatAttendanceStatus,
} from '@/lib/attendance-display';
import { index, show } from '@/routes/employees';
import type { AttendanceRecordView, EmployeeDeviceRecord } from '@/types';

const formatDate = (value: string): string => {
    return new Date(`${value}T00:00:00`).toLocaleDateString('es-MX');
};

const formatDateTime = (value: string | null): string => {
    if (value === null) {
        return 'Sin registro';
    }

    return new Date(value).toLocaleString('es-MX');
};

export default function EmployeeShow({
    employee,
    filters,
    summary,
    attendance,
    devices,
}: {
    employee: { id: number; name: string; username: string; email: string; attendance_starts_at: string | null };
    filters: { from: string; to: string };
    summary: { attendances: number; tardies: number; absences: number; absence_equivalents: number };
    attendance: AttendanceRecordView[];
    devices: EmployeeDeviceRecord[];
}) {
    return (
        <>
            <Head title={`Empleado · ${employee.name}`} />

            <div className="min-h-full space-y-6 bg-white p-4">
                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title={employee.name}
                        description={`@${employee.username} · ${employee.email}`}
                    />
                    <p className="mt-3 text-sm text-stone-500">
                        Inicio de asistencia: {employee.attendance_starts_at ?? 'Se toma la fecha de registro'}
                    </p>

                    <form
                        className="mt-6 grid gap-3 md:grid-cols-[220px_220px_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);
                            const from = String(formData.get('from') ?? '');
                            const to = String(formData.get('to') ?? '');

                            router.get(show.url(employee.id), { from, to }, { preserveState: true, replace: true });
                        }}
                    >
                        <Input type="date" name="from" defaultValue={filters.from} className="border-stone-200 bg-white" />
                        <Input type="date" name="to" defaultValue={filters.to} className="border-stone-200 bg-white" />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">
                                Filtrar
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => router.get(show.url(employee.id), {}, { preserveState: true, replace: true })}
                            >
                                Limpiar
                            </Button>
                        </div>
                    </form>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {[
                        { label: 'Asistencias', value: summary.attendances, accent: 'text-nopal-700' },
                        { label: 'Retardos', value: summary.tardies, accent: 'text-amber-700' },
                        { label: 'Faltas', value: summary.absences, accent: 'text-rose-700' },
                        { label: 'Equivalentes', value: summary.absence_equivalents, accent: 'text-stone-700' },
                    ].map((card) => (
                        <div
                            key={card.label}
                            className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm"
                        >
                            <p className="text-sm font-medium text-stone-500">{card.label}</p>
                            <p className={`mt-3 text-3xl font-semibold ${card.accent}`}>{card.value}</p>
                        </div>
                    ))}
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_380px]">
                    <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                        <div className="border-b border-stone-200 px-6 py-4">
                            <Heading
                                variant="small"
                                title="Historial de asistencia"
                                description="Entrada, salida, estado, codigos del dia y dispositivo usado. Los dias no laborales o fuera de periodo no generan falta."
                            />
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-stone-100 text-left text-nopal-700">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Fecha</th>
                                        <th className="px-6 py-4 font-medium">Entrada</th>
                                        <th className="px-6 py-4 font-medium">Salida</th>
                                        <th className="px-6 py-4 font-medium">Detalle</th>
                                        <th className="px-6 py-4 font-medium">Dispositivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {attendance.map((record) => (
                                        <tr key={record.id} className="border-t border-stone-200 align-top">
                                            <td className="px-6 py-4 text-stone-600">
                                                <div className="font-medium text-stone-900">
                                                    {formatDate(record.attendance_date)}
                                                </div>
                                                <div className="text-xs text-stone-400">
                                                    Esperada {new Date(record.expected_check_in_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}
                                                    {' / '}
                                                    {new Date(record.expected_check_out_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <Badge variant="outline" className={attendanceStatusBadgeClass(record.check_in_status)}>
                                                    {formatAttendanceStatus(record.check_in_status)}
                                                </Badge>
                                                <div className="mt-2">{formatDateTime(record.check_in_at)}</div>
                                                {record.late_minutes > 0 && (
                                                    <div className="mt-1 text-xs text-amber-700">
                                                        {record.late_minutes} min tarde
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <Badge variant="outline" className={attendanceStatusBadgeClass(record.check_out_status)}>
                                                    {formatAttendanceStatus(record.check_out_status)}
                                                </Badge>
                                                <div className="mt-2">{formatDateTime(record.check_out_at)}</div>
                                                {record.early_leave_minutes > 0 && (
                                                    <div className="mt-1 text-xs text-orange-700">
                                                        {record.early_leave_minutes} min antes
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <div>Vivo: {formatAttendanceStatus(record.live_status)}</div>
                                                <div className="text-xs text-stone-400">
                                                    Entrada: {record.entry_code ?? 'N/A'} · Salida: {record.exit_code ?? 'N/A'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <div className="text-xs">
                                                    {record.check_in_device?.device_name ?? 'Sin dispositivo de entrada'}
                                                </div>
                                                <div className="mt-2 text-xs">
                                                    {record.check_out_device?.device_name ?? 'Sin dispositivo de salida'}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside className="space-y-6">
                        <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <Heading
                                variant="small"
                                title="Dispositivos detectados"
                                description="Equipos desde los que ha iniciado sesion o marcado asistencia."
                            />

                            <div className="mt-4 space-y-3">
                                {devices.length === 0 ? (
                                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-6 text-sm text-stone-500">
                                        Aun no hay dispositivos registrados.
                                    </div>
                                ) : (
                                    devices.map((device) => (
                                        <div key={device.id} className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                            <div className="font-medium text-stone-900">{device.device_name}</div>
                                            <div className="mt-1 text-xs text-stone-500">
                                                {device.browser_name ?? 'Navegador'} · {device.platform_name ?? 'Plataforma'}
                                            </div>
                                            <div className="mt-2 text-xs text-stone-500">
                                                IP: {device.last_ip ?? 'Sin IP'}
                                            </div>
                                            <div className="text-xs text-stone-500">
                                                Ultimo uso: {formatDateTime(device.last_seen_at)}
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </>
    );
}

EmployeeShow.layout = {
    breadcrumbs: [
        { title: 'Empleados', href: index() },
        { title: 'Detalle', href: '#' },
    ],
};
