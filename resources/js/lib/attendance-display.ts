const attendanceStatusLabels: Record<string, string> = {
    pending: 'Pendiente',
    on_time: 'A tiempo',
    tardy: 'Retardo',
    absent: 'Falta',
    completed: 'Completa',
    early: 'Salida anticipada',
    off_day: 'Dia no laboral',
    not_started: 'Fuera de periodo',
};

export const formatAttendanceStatus = (value: string): string => {
    return attendanceStatusLabels[value] ?? value;
};

export const attendanceStatusBadgeClass = (value: string): string => {
    return (
        {
            pending: 'bg-stone-100 text-stone-700 border-stone-200',
            on_time: 'bg-nopal-50 text-nopal-700 border-nopal-200',
            tardy: 'bg-amber-50 text-amber-800 border-amber-200',
            absent: 'bg-rose-50 text-rose-700 border-rose-200',
            completed: 'bg-sky-50 text-sky-800 border-sky-200',
            early: 'bg-orange-50 text-orange-800 border-orange-200',
            off_day: 'bg-stone-100 text-stone-700 border-stone-200',
            not_started: 'bg-violet-50 text-violet-700 border-violet-200',
        }[value] ?? 'bg-stone-100 text-stone-700 border-stone-200'
    );
};

export const attendanceStatusAccentClass = (value: string): string => {
    return (
        {
            pending: 'text-stone-700',
            on_time: 'text-nopal-700',
            tardy: 'text-amber-700',
            absent: 'text-rose-700',
            completed: 'text-sky-700',
            early: 'text-orange-700',
            off_day: 'text-stone-700',
            not_started: 'text-violet-700',
        }[value] ?? 'text-stone-700'
    );
};
