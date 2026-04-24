import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CustomerFormData = {
    name: string;
    customer_type: string;
    phone: string;
    email: string;
    address: string;
    is_active: boolean;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    initialValues?: Partial<CustomerFormData>;
};

export default function CustomerForm({
    title,
    description,
    submitLabel,
    action,
    method,
    initialValues,
}: Props) {
    const form = useForm<CustomerFormData>({
        name: initialValues?.name ?? '',
        customer_type: initialValues?.customer_type ?? '',
        phone: initialValues?.phone ?? '',
        email: initialValues?.email ?? '',
        address: initialValues?.address ?? '',
        is_active: initialValues?.is_active ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = { preserveScroll: true };

        if (method === 'patch') {
            form.patch(action, options);

            return;
        }

        form.post(action, options);
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6 lg:grid-cols-2">
                {[
                    ['name', 'Nombre', 'Tortilleria San Miguel'],
                    ['customer_type', 'Tipo de cliente', 'Mostrador o reparto'],
                    ['phone', 'Telefono', '614-000-0000'],
                    ['email', 'Email', 'cliente@nopalgreen.local'],
                    ['address', 'Direccion', 'Calle Principal 123'],
                ].map(([field, label, placeholder]) => (
                    <div key={field} className={`grid gap-2 ${field === 'address' ? 'lg:col-span-2' : ''}`}>
                        <Label htmlFor={field} className="font-semibold text-nopal-700">
                            {label}
                        </Label>
                        <Input
                            id={field}
                            value={form.data[field as keyof CustomerFormData] as string}
                            onChange={(event) =>
                                form.setData(field as keyof CustomerFormData, event.target.value as never)
                            }
                            placeholder={placeholder}
                            className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                        />
                        <InputError message={form.errors[field as keyof typeof form.errors]} />
                    </div>
                ))}

                <label className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                    <input
                        type="checkbox"
                        className="h-4 w-4 accent-[var(--color-nopal-500)]"
                        checked={form.data.is_active}
                        onChange={(event) => form.setData('is_active', event.target.checked)}
                    />
                    <span>Cliente activo</span>
                </label>
            </div>

            <div className="flex items-center gap-3">
                <Button className="rounded-xl" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {form.recentlySuccessful && <p className="text-sm text-stone-500">Guardado.</p>}
            </div>
        </form>
    );
}
