import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { activeLabel, formatPresentationOwnerType } from '@/lib/inventory-display';
import type { SelectOption, UnitOption } from '@/types';

type PresentationFormData = {
    owner_type: string;
    owner_id: string;
    name: string;
    quantity: string;
    unit_id: string;
    barcode: string;
    is_active: boolean;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    ownerTypes: string[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    units: UnitOption[];
    initialValues?: Partial<PresentationFormData>;
};

export default function PresentationForm({
    title,
    description,
    submitLabel,
    action,
    method,
    ownerTypes,
    rawMaterials,
    products,
    units,
    initialValues,
}: Props) {
    const initialOwnerType = initialValues?.owner_type ?? ownerTypes[0] ?? 'raw_material';
    const ownerOptions =
        initialOwnerType === 'product' ? products : rawMaterials;

    const form = useForm<PresentationFormData>({
        owner_type: initialOwnerType,
        owner_id: initialValues?.owner_id ?? String(ownerOptions[0]?.id ?? ''),
        name: initialValues?.name ?? '',
        quantity: initialValues?.quantity ?? '',
        unit_id: initialValues?.unit_id ?? String(units[0]?.id ?? ''),
        barcode: initialValues?.barcode ?? '',
        is_active: initialValues?.is_active ?? true,
    });

    const currentOwnerOptions =
        form.data.owner_type === 'product' ? products : rawMaterials;

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            owner_id: Number(form.data.owner_id),
            unit_id: Number(form.data.unit_id),
            barcode: form.data.barcode === '' ? null : form.data.barcode,
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
                    <Label
                        htmlFor="owner_type"
                        className="font-semibold text-nopal-700"
                    >
                        Tipo de presentacion
                    </Label>
                    <Select
                        value={form.data.owner_type}
                        onValueChange={(value) => {
                            const nextOptions =
                                value === 'product' ? products : rawMaterials;

                            form.setData((current) => ({
                                ...current,
                                owner_type: value,
                                owner_id: String(nextOptions[0]?.id ?? ''),
                            }));
                        }}
                        disabled={method === 'patch'}
                    >
                        <SelectTrigger
                            id="owner_type"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
                            <SelectValue placeholder="Selecciona un tipo" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {ownerTypes.map((ownerType) => (
                                <SelectItem key={ownerType} value={ownerType}>
                                    {formatPresentationOwnerType(ownerType)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.owner_type} />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="owner_id"
                        className="font-semibold text-nopal-700"
                    >
                        {form.data.owner_type === 'product'
                            ? 'Producto'
                            : 'Materia prima'}
                    </Label>
                    <Select
                        value={form.data.owner_id}
                        onValueChange={(value) => form.setData('owner_id', value)}
                    >
                        <SelectTrigger
                            id="owner_id"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
                            <SelectValue placeholder="Selecciona un registro" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {currentOwnerOptions.map((option) => (
                                <SelectItem key={option.id} value={String(option.id)}>
                                    {option.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.owner_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name" className="font-semibold text-nopal-700">
                        Nombre
                    </Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        placeholder="Costal 25 kg"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="quantity"
                        className="font-semibold text-nopal-700"
                    >
                        Cantidad
                    </Label>
                    <Input
                        id="quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        value={form.data.quantity}
                        onChange={(event) =>
                            form.setData('quantity', event.target.value)
                        }
                        placeholder="25"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.quantity} />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="unit_id"
                        className="font-semibold text-nopal-700"
                    >
                        Unidad
                    </Label>
                    <Select
                        value={form.data.unit_id}
                        onValueChange={(value) => form.setData('unit_id', value)}
                    >
                        <SelectTrigger
                            id="unit_id"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
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
                    <InputError message={form.errors.unit_id} />
                </div>

                <div className="grid gap-2">
                    <Label
                        htmlFor="barcode"
                        className="font-semibold text-nopal-700"
                    >
                        Codigo de barras
                    </Label>
                    <Input
                        id="barcode"
                        value={form.data.barcode}
                        onChange={(event) =>
                            form.setData('barcode', event.target.value)
                        }
                        placeholder="7501234567890"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.barcode} />
                </div>

                <label className="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700">
                    <input
                        type="checkbox"
                        className="h-4 w-4 accent-[var(--color-nopal-500)]"
                        checked={form.data.is_active}
                        onChange={(event) =>
                            form.setData('is_active', event.target.checked)
                        }
                    />
                    <span>Estado: {activeLabel(form.data.is_active)}</span>
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
