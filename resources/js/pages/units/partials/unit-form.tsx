import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type UnitFormData = {
    name: string;
    code: string;
    decimal_places: number;
    is_active: boolean;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    initialValues?: Partial<UnitFormData>;
};

export default function UnitForm({
    title,
    description,
    submitLabel,
    action,
    method,
    initialValues,
}: Props) {
    const form = useForm<UnitFormData>({
        name: initialValues?.name ?? '',
        code: initialValues?.code ?? '',
        decimal_places: initialValues?.decimal_places ?? 0,
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

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="name" className="font-semibold text-nopal-700">
                        Nombre
                    </Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        placeholder="Kilogramo"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="code" className="font-semibold text-nopal-700">
                        Codigo
                    </Label>
                    <Input
                        id="code"
                        value={form.data.code}
                        onChange={(event) => form.setData('code', event.target.value)}
                        placeholder="kg"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.code} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="decimal_places" className="font-semibold text-nopal-700">
                        Decimales
                    </Label>
                    <Input
                        id="decimal_places"
                        type="number"
                        min={0}
                        max={3}
                        value={form.data.decimal_places}
                        onChange={(event) =>
                            form.setData('decimal_places', Number(event.target.value))
                        }
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.decimal_places} />
                </div>

                <label className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                    <input
                        type="checkbox"
                        className="h-4 w-4 accent-[var(--color-nopal-500)]"
                        checked={form.data.is_active}
                        onChange={(event) => form.setData('is_active', event.target.checked)}
                    />
                    <span>Unidad activa</span>
                </label>
            </div>

            <div className="flex items-center gap-3">
                <Button className="rounded-xl" disabled={form.processing}>
                    {submitLabel}
                </Button>
                {form.recentlySuccessful && (
                    <p className="text-sm text-stone-500">Guardado.</p>
                )}
            </div>
        </form>
    );
}
