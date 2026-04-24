import { Head, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    attendanceStatusBadgeClass,
    formatAttendanceStatus,
} from '@/lib/attendance-display';
import { edit, store } from '@/routes/attendance-mark';
import type { AttendanceRecordView, AttendanceSettings, EmployeeDeviceRecord } from '@/types';

const formatDate = (value: string): string => {
    return new Date(`${value}T00:00:00`).toLocaleDateString('es-MX');
};

const formatDateTime = (value: string | null): string => {
    if (value === null) {
        return 'Sin registro';
    }

    return new Date(value).toLocaleString('es-MX');
};

export default function AttendanceMarkIndex({
    employee,
    settings,
    today,
    current_device,
}: {
    employee: { id: number; name: string; username: string };
    settings: AttendanceSettings;
    today: AttendanceRecordView;
    current_device: EmployeeDeviceRecord | null;
}) {
    const entryForm = useForm({
        mark_type: 'entry',
        code: '',
    });

    const exitForm = useForm({
        mark_type: 'exit',
        code: '',
    });

    return (
        <>
            <Head title="Mi asistencia" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title={`Mi asistencia · ${employee.name}`}
                        description={`@${employee.username} · Marca entrada y salida con tu codigo unico del dia.`}
                    />

                    <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm">
                            <p className="text-sm font-medium text-stone-500">Fecha</p>
                            <p className="mt-3 text-2xl font-semibold text-stone-900">{formatDate(today.attendance_date)}</p>
                        </div>
                        <div className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm">
                            <p className="text-sm font-medium text-stone-500">Estado actual</p>
                            <div className="mt-3">
                                <Badge variant="outline" className={attendanceStatusBadgeClass(today.live_status)}>
                                    {formatAttendanceStatus(today.live_status)}
                                </Badge>
                            </div>
                        </div>
                        <div className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm">
                            <p className="text-sm font-medium text-stone-500">Entrada esperada</p>
                            <p className="mt-3 text-2xl font-semibold text-nopal-700">{settings.check_in_time}</p>
                            <p className="mt-1 text-xs text-stone-500">
                                Tolerancia {settings.tolerance_minutes} min · falta desde {settings.absence_after_time}
                            </p>
                        </div>
                        <div className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm">
                            <p className="text-sm font-medium text-stone-500">Salida esperada</p>
                            <p className="mt-3 text-2xl font-semibold text-sky-700">{settings.check_out_time}</p>
                            <p className="mt-1 text-xs text-stone-500">
                                {settings.tardies_before_absence} retardos equivalen a una falta
                            </p>
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
                    <div className="space-y-6">
                        <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <Heading
                                variant="small"
                                title="Codigos del dia"
                                description="Cada marca usa un codigo unico. Comparte pantalla solo con el empleado correcto."
                            />

                            <div className="mt-4 grid gap-4 md:grid-cols-2">
                                <div className="rounded-2xl border border-nopal-200 bg-nopal-50 p-4">
                                    <p className="text-sm font-medium text-stone-500">Codigo de entrada</p>
                                    <p className="mt-2 text-3xl font-semibold tracking-[0.2em] text-nopal-700">
                                        {today.entry_code ?? 'No aplica'}
                                    </p>
                                    <p className="mt-2 text-xs text-stone-600">
                                        Registrada: {formatDateTime(today.check_in_at)}
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                                    <p className="text-sm font-medium text-stone-500">Codigo de salida</p>
                                    <p className="mt-2 text-3xl font-semibold tracking-[0.2em] text-sky-700">
                                        {today.exit_code ?? 'No aplica'}
                                    </p>
                                    <p className="mt-2 text-xs text-stone-600">
                                        Registrada: {formatDateTime(today.check_out_at)}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-6 lg:grid-cols-2">
                            <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                                <Heading
                                    variant="small"
                                    title="Marcar entrada"
                                    description="Usa el codigo de entrada vigente para registrar la llegada."
                                />

                                <form
                                    className="mt-4 space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        entryForm.post(store.url(), { preserveScroll: true });
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="entry-code" className="font-medium text-stone-800">Codigo</Label>
                                        <Input
                                            id="entry-code"
                                            value={entryForm.data.code}
                                            maxLength={6}
                                            disabled={today.entry_code === null || today.check_in_at !== null}
                                            onChange={(event) => entryForm.setData('code', event.target.value.toUpperCase())}
                                            className="border-stone-300 bg-white text-stone-900 uppercase tracking-[0.3em]"
                                        />
                                        <InputError message={entryForm.errors.code} />
                                    </div>

                                    <Button disabled={entryForm.processing || today.entry_code === null || today.check_in_at !== null}>
                                        {today.entry_code === null ? 'Sin marca hoy' : 'Registrar entrada'}
                                    </Button>
                                </form>
                            </section>

                            <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                                <Heading
                                    variant="small"
                                    title="Marcar salida"
                                    description="La salida requiere que la entrada ya exista y valida el codigo del dia."
                                />

                                <form
                                    className="mt-4 space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        exitForm.post(store.url(), { preserveScroll: true });
                                    }}
                                >
                                    <div className="grid gap-2">
                                        <Label htmlFor="exit-code" className="font-medium text-stone-800">Codigo</Label>
                                        <Input
                                            id="exit-code"
                                            value={exitForm.data.code}
                                            maxLength={6}
                                            disabled={today.entry_code === null || today.check_in_at === null || today.check_out_at !== null}
                                            onChange={(event) => exitForm.setData('code', event.target.value.toUpperCase())}
                                            className="border-stone-300 bg-white text-stone-900 uppercase tracking-[0.3em]"
                                        />
                                        <InputError message={exitForm.errors.code} />
                                    </div>

                                    <Button
                                        disabled={exitForm.processing || today.entry_code === null || today.check_in_at === null || today.check_out_at !== null}
                                    >
                                        {today.exit_code === null ? 'Sin marca hoy' : 'Registrar salida'}
                                    </Button>
                                </form>
                            </section>
                        </div>
                    </div>

                    <aside className="space-y-6">
                        <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <Heading
                                variant="small"
                                title="Resumen de hoy"
                                description="Estado actual y dispositivo con el que se esta trabajando."
                            />

                            <div className="mt-4 space-y-4">
                                {today.entry_code === null && (
                                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-4 text-sm text-stone-600">
                                        Hoy no corresponde marcar asistencia porque es un dia no laboral o aun no inicia el periodo de asistencia del usuario.
                                    </div>
                                )}

                                <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                    <div className="text-sm text-stone-500">Estado de entrada</div>
                                    <Badge variant="outline" className={`mt-2 ${attendanceStatusBadgeClass(today.check_in_status)}`}>
                                        {formatAttendanceStatus(today.check_in_status)}
                                    </Badge>
                                </div>

                                <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                    <div className="text-sm text-stone-500">Estado de salida</div>
                                    <Badge variant="outline" className={`mt-2 ${attendanceStatusBadgeClass(today.check_out_status)}`}>
                                        {formatAttendanceStatus(today.check_out_status)}
                                    </Badge>
                                </div>

                                <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                                    <div>Entrada: {formatDateTime(today.check_in_at)}</div>
                                    <div className="mt-1">Salida: {formatDateTime(today.check_out_at)}</div>
                                    <div className="mt-1">Min. tarde: {today.late_minutes}</div>
                                    <div className="mt-1">Min. salida anticipada: {today.early_leave_minutes}</div>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <Heading
                                variant="small"
                                title="Dispositivo actual"
                                description="Sirve para detectar marcas hechas desde otro equipo o sesion."
                            />

                            <div className="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
                                {current_device === null ? (
                                    <p>Aun no hay un dispositivo registrado para esta sesion.</p>
                                ) : (
                                    <>
                                        <div className="font-medium text-stone-900">{current_device.device_name}</div>
                                        <div className="mt-1">
                                            {current_device.browser_name ?? 'Navegador'} · {current_device.platform_name ?? 'Plataforma'}
                                        </div>
                                        <div className="mt-1">IP: {current_device.last_ip ?? 'Sin IP'}</div>
                                        <div className="mt-1">Ultimo uso: {formatDateTime(current_device.last_seen_at)}</div>
                                    </>
                                )}
                            </div>
                        </div>
                    </aside>
                </section>
            </div>
        </>
    );
}

AttendanceMarkIndex.layout = {
    breadcrumbs: [{ title: 'Mi asistencia', href: edit() }],
};
