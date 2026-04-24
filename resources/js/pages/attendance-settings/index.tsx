import { Head, useForm, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, update } from '@/routes/attendance-settings';
import type { AttendanceSettings } from '@/types';

const workDayOptions = [
    { value: 'monday', label: 'Lunes' },
    { value: 'tuesday', label: 'Martes' },
    { value: 'wednesday', label: 'Miercoles' },
    { value: 'thursday', label: 'Jueves' },
    { value: 'friday', label: 'Viernes' },
    { value: 'saturday', label: 'Sabado' },
    { value: 'sunday', label: 'Domingo' },
];

export default function AttendanceSettingsIndex({
    settings,
}: {
    settings: AttendanceSettings;
}) {
    const { status } = usePage<{ status?: string }>().props;
    const form = useForm({
        check_in_time: settings.check_in_time,
        check_out_time: settings.check_out_time,
        tolerance_minutes: String(settings.tolerance_minutes),
        absence_after_time: settings.absence_after_time,
        tardies_before_absence: String(settings.tardies_before_absence),
        work_days: settings.work_days,
    });

    return (
        <>
            <Head title="Asistencia" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Configuracion de asistencia"
                        description="Define horario de entrada, salida, tolerancia, hora limite y conversion de retardos a falta."
                    />

                    {typeof status === 'string' && status !== '' && (
                        <div className="mt-6 rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <form
                        className="mt-6 space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();

                            form.patch(update.url(), { preserveScroll: true });
                        }}
                    >
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="check_in_time" className="font-medium text-stone-800">Hora de entrada</Label>
                                <Input
                                    id="check_in_time"
                                    type="time"
                                    value={form.data.check_in_time}
                                    onChange={(event) => form.setData('check_in_time', event.target.value)}
                                    className="border-stone-300 bg-white text-stone-900"
                                />
                                <InputError message={form.errors.check_in_time} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="check_out_time" className="font-medium text-stone-800">Hora de salida</Label>
                                <Input
                                    id="check_out_time"
                                    type="time"
                                    value={form.data.check_out_time}
                                    onChange={(event) => form.setData('check_out_time', event.target.value)}
                                    className="border-stone-300 bg-white text-stone-900"
                                />
                                <InputError message={form.errors.check_out_time} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tolerance_minutes" className="font-medium text-stone-800">Tolerancia en minutos</Label>
                                <Input
                                    id="tolerance_minutes"
                                    type="number"
                                    min="0"
                                    value={form.data.tolerance_minutes}
                                    onChange={(event) => form.setData('tolerance_minutes', event.target.value)}
                                    className="border-stone-300 bg-white text-stone-900"
                                />
                                <InputError message={form.errors.tolerance_minutes} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="absence_after_time" className="font-medium text-stone-800">Hora a partir de la cual es falta</Label>
                                <Input
                                    id="absence_after_time"
                                    type="time"
                                    value={form.data.absence_after_time}
                                    onChange={(event) => form.setData('absence_after_time', event.target.value)}
                                    className="border-stone-300 bg-white text-stone-900"
                                />
                                <InputError message={form.errors.absence_after_time} />
                            </div>
                        </div>

                        <div className="grid gap-2 lg:max-w-sm">
                            <Label htmlFor="tardies_before_absence" className="font-medium text-stone-800">
                                Retardos que equivalen a una falta
                            </Label>
                            <Input
                                id="tardies_before_absence"
                                type="number"
                                min="1"
                                value={form.data.tardies_before_absence}
                                onChange={(event) => form.setData('tardies_before_absence', event.target.value)}
                                className="border-stone-300 bg-white text-stone-900"
                            />
                            <InputError message={form.errors.tardies_before_absence} />
                        </div>

                        <div className="space-y-4">
                            <Heading
                                variant="small"
                                title="Dias laborales"
                                description="Solo en estos dias se generan codigos y se cuentan faltas o retardos."
                            />

                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                {workDayOptions.map((day) => (
                                    <label
                                        key={day.value}
                                        className="flex items-start gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700"
                                    >
                                        <input
                                            type="checkbox"
                                            className="mt-1 h-4 w-4 accent-[var(--color-nopal-500)]"
                                            checked={form.data.work_days.includes(day.value)}
                                            onChange={(event) =>
                                                form.setData(
                                                    'work_days',
                                                    event.target.checked
                                                        ? [...form.data.work_days, day.value]
                                                        : form.data.work_days.filter((value) => value !== day.value),
                                                )
                                            }
                                        />
                                        <span className="font-semibold text-nopal-700">{day.label}</span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={form.errors.work_days} />
                        </div>

                        <Button disabled={form.processing}>
                            Guardar configuracion
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}

AttendanceSettingsIndex.layout = {
    breadcrumbs: [{ title: 'Asistencia', href: edit() }],
};
