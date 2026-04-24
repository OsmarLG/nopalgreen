import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { SelectOption, UnitOption } from '@/types';

const NO_SUPPLIER_VALUE = '__none__';

type RawMaterialFormData = {
    name: string;
    description: string;
    base_unit_id: string;
    supplier_id: string;
    is_active: boolean;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    units: UnitOption[];
    suppliers: SelectOption[];
    initialValues?: Partial<RawMaterialFormData>;
};

export default function RawMaterialForm({
    title,
    description,
    submitLabel,
    action,
    method,
    units,
    suppliers,
    initialValues,
}: Props) {
    const form = useForm<RawMaterialFormData>({
        name: initialValues?.name ?? '',
        description: initialValues?.description ?? '',
        base_unit_id: initialValues?.base_unit_id ?? String(units[0]?.id ?? ''),
        supplier_id: initialValues?.supplier_id ?? '',
        is_active: initialValues?.is_active ?? true,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            base_unit_id: Number(form.data.base_unit_id),
            supplier_id:
                form.data.supplier_id === NO_SUPPLIER_VALUE || form.data.supplier_id === ''
                    ? null
                    : Number(form.data.supplier_id),
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
                    <Label htmlFor="name" className="font-semibold text-nopal-700">Nombre</Label>
                    <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} placeholder="Maiz Blanco" className="h-12 rounded-xl border-stone-200 bg-white text-stone-900" />
                    <InputError message={form.errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="base_unit_id" className="font-semibold text-nopal-700">Unidad base</Label>
                    <Select value={form.data.base_unit_id} onValueChange={(value) => form.setData('base_unit_id', value)}>
                        <SelectTrigger id="base_unit_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona una unidad" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {units.map((unit) => (
                                <SelectItem key={unit.id} value={String(unit.id)}>
                                    {unit.name} ({unit.code})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.base_unit_id} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="description" className="font-semibold text-nopal-700">Descripcion</Label>
                    <Input id="description" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} placeholder="Materia prima base para produccion." className="h-12 rounded-xl border-stone-200 bg-white text-stone-900" />
                    <InputError message={form.errors.description} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="supplier_id" className="font-semibold text-nopal-700">Proveedor principal</Label>
                    <Select
                        value={form.data.supplier_id === '' ? NO_SUPPLIER_VALUE : form.data.supplier_id}
                        onValueChange={(value) =>
                            form.setData(
                                'supplier_id',
                                value === NO_SUPPLIER_VALUE ? '' : value,
                            )
                        }
                    >
                        <SelectTrigger id="supplier_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Sin proveedor" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            <SelectItem value={NO_SUPPLIER_VALUE}>Sin proveedor</SelectItem>
                            {suppliers.map((supplier) => (
                                <SelectItem key={supplier.id} value={String(supplier.id)}>
                                    {supplier.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.supplier_id} />
                </div>

                <label className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                    <input type="checkbox" className="h-4 w-4 accent-[var(--color-nopal-500)]" checked={form.data.is_active} onChange={(event) => form.setData('is_active', event.target.checked)} />
                    <span>Materia prima activa</span>
                </label>
            </div>

            <div className="flex items-center gap-3">
                <Button className="rounded-xl" disabled={form.processing}>{submitLabel}</Button>
                {form.recentlySuccessful && <p className="text-sm text-stone-500">Guardado.</p>}
            </div>
        </form>
    );
}
