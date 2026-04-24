import { CalendarDays, Clock3 } from 'lucide-react';
import { useRef } from 'react';
import { Input } from '@/components/ui/input';

type Props = {
    id?: string;
    value: string;
    onChange: (value: string) => void;
    includeTime?: boolean;
    className?: string;
    disabled?: boolean;
};

const splitValue = (value: string): { date: string; time: string } => {
    if (value === '') {
        return { date: '', time: '' };
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');
    const [date, time = ''] = normalized.split('T');

    return {
        date,
        time: time.slice(0, 5),
    };
};

const mergeValue = (date: string, time: string, includeTime: boolean): string => {
    if (date === '') {
        return '';
    }

    if (!includeTime) {
        return date;
    }

    return `${date}T${time || '00:00'}`;
};

export function DateTimePicker({
    id,
    value,
    onChange,
    includeTime = true,
    className,
    disabled = false,
}: Props) {
    const { date, time } = splitValue(value);
    const dateInputRef = useRef<HTMLInputElement>(null);
    const timeInputRef = useRef<HTMLInputElement>(null);

    const openPicker = (input: HTMLInputElement | null): void => {
        if (input === null) {
            return;
        }

        if (disabled) {
            return;
        }

        input.focus();
        input.showPicker?.();
    };

    return (
        <div className={`grid gap-3 sm:grid-cols-[1.35fr_1fr] ${className ?? ''}`.trim()}>
            <div className="relative">
                <button
                    type="button"
                    onClick={() => openPicker(dateInputRef.current)}
                    disabled={disabled}
                    className="absolute top-1/2 left-3 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-stone-400 transition hover:bg-stone-100 hover:text-stone-600 disabled:pointer-events-none disabled:opacity-60"
                    aria-label="Abrir selector de fecha"
                >
                    <CalendarDays className="size-4" />
                </button>
                <Input
                    ref={dateInputRef}
                    id={id}
                    type="date"
                    value={date}
                    onChange={(event) => onChange(mergeValue(event.target.value, time, includeTime))}
                    onClick={() => openPicker(dateInputRef.current)}
                    onFocus={() => openPicker(dateInputRef.current)}
                    disabled={disabled}
                    className="h-12 rounded-xl border-stone-200 bg-white pl-11 text-stone-900 disabled:bg-stone-100 disabled:text-stone-500"
                />
            </div>

            {includeTime && (
                <div className="relative">
                    <button
                        type="button"
                        onClick={() => openPicker(timeInputRef.current)}
                        disabled={disabled}
                        className="absolute top-1/2 left-3 z-10 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-stone-400 transition hover:bg-stone-100 hover:text-stone-600 disabled:pointer-events-none disabled:opacity-60"
                        aria-label="Abrir selector de hora"
                    >
                        <Clock3 className="size-4" />
                    </button>
                    <Input
                        ref={timeInputRef}
                        type="time"
                        value={time}
                        onChange={(event) => onChange(mergeValue(date, event.target.value, includeTime))}
                        onClick={() => openPicker(timeInputRef.current)}
                        onFocus={() => openPicker(timeInputRef.current)}
                        disabled={disabled}
                        className="h-12 rounded-xl border-stone-200 bg-white pl-11 text-stone-900 disabled:bg-stone-100 disabled:text-stone-500"
                    />
                </div>
            )}
        </div>
    );
}
