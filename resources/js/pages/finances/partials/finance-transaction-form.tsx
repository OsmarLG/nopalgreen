import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatFinanceStatus, formatFinanceType } from '@/lib/finance-display';

type FinanceTransactionFormData = {
    transaction_type: string;
    concept: string;
    detail: string;
    amount: string;
    status: string;
    occurred_at: string;
    notes: string;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    typeOptions: string[];
    statusOptions: string[];
    initialValues?: Partial<FinanceTransactionFormData>;
};

const toDateTimeLocalValue = (value: string): string => {
    if (value === '') {
        return '';
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');

    return normalized.slice(0, 16);
};

export default function FinanceTransactionForm({
    title,
    description,
    submitLabel,
    action,
    method,
    typeOptions,
    statusOptions,
    initialValues,
}: Props) {
    const form = useForm<FinanceTransactionFormData>({
        transaction_type: initialValues?.transaction_type ?? typeOptions[0] ?? 'expense',
        concept: initialValues?.concept ?? '',
        detail: initialValues?.detail ?? '',
        amount: initialValues?.amount ?? '',
        status: initialValues?.status ?? statusOptions[0] ?? 'posted',
        occurred_at: toDateTimeLocalValue(initialValues?.occurred_at ?? ''),
        notes: initialValues?.notes ?? '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            amount: Number(form.data.amount),
            occurred_at: form.data.occurred_at,
        };

        form.transform(() => payload);

        if (method === 'patch') {
            form.patch(action, { preserveScroll: true });

            return;
        }

        form.post(action, { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="transaction_type" className="font-semibold text-nopal-700">
                        Tipo
                    </Label>
                    <Select value={form.data.transaction_type} onValueChange={(value) => form.setData('transaction_type', value)}>
                        <SelectTrigger id="transaction_type" className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un tipo" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {typeOptions.map((typeOption) => (
                                <SelectItem key={typeOption} value={typeOption}>
                                    {formatFinanceType(typeOption)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.transaction_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status" className="font-semibold text-nopal-700">
                        Estado
                    </Label>
                    <Select value={form.data.status} onValueChange={(value) => form.setData('status', value)}>
                        <SelectTrigger id="status" className="h-12 rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un estado" />
                        </SelectTrigger>
                        <SelectContent className="rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {statusOptions.map((statusOption) => (
                                <SelectItem key={statusOption} value={statusOption}>
                                    {formatFinanceStatus(statusOption)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="concept" className="font-semibold text-nopal-700">
                        Concepto
                    </Label>
                    <Input
                        id="concept"
                        value={form.data.concept}
                        onChange={(event) => form.setData('concept', event.target.value)}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.concept} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="detail" className="font-semibold text-nopal-700">
                        Detalle
                    </Label>
                    <textarea
                        id="detail"
                        value={form.data.detail}
                        onChange={(event) => form.setData('detail', event.target.value)}
                        className="min-h-28 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none placeholder:text-stone-400 focus:border-nopal-300"
                    />
                    <InputError message={form.errors.detail} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="amount" className="font-semibold text-nopal-700">
                        Monto
                    </Label>
                    <Input
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        value={form.data.amount}
                        onChange={(event) => form.setData('amount', event.target.value)}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.amount} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="occurred_at" className="font-semibold text-nopal-700">
                        Fecha y hora
                    </Label>
                    <DateTimePicker
                        id="occurred_at"
                        value={form.data.occurred_at}
                        onChange={(value) => form.setData('occurred_at', value)}
                    />
                    <InputError message={form.errors.occurred_at} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="notes" className="font-semibold text-nopal-700">
                        Notas
                    </Label>
                    <textarea
                        id="notes"
                        value={form.data.notes}
                        onChange={(event) => form.setData('notes', event.target.value)}
                        className="min-h-24 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none placeholder:text-stone-400 focus:border-nopal-300"
                    />
                    <InputError message={form.errors.notes} />
                </div>
            </div>

            <Button disabled={form.processing}>{submitLabel}</Button>
        </form>
    );
}
