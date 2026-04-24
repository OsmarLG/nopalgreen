import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { DateTimePicker } from '@/components/ui/date-time-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPresentationOwnerType, formatPurchaseStatus } from '@/lib/inventory-display';
import type { PurchaseCatalogOption, SelectOption } from '@/types';

type PurchaseItemFormData = {
    item_type: 'raw_material' | 'product';
    item_id: string;
    presentation_id: string;
    quantity: string;
    unit_cost: string;
    total: string;
};

type PurchaseFormData = {
    supplier_id: string;
    status: string;
    purchased_at: string;
    notes: string;
    items: PurchaseItemFormData[];
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    suppliers: SelectOption[];
    rawMaterials: PurchaseCatalogOption[];
    products: PurchaseCatalogOption[];
    itemTypes: string[];
    statuses: string[];
    initialValues?: Partial<PurchaseFormData>;
};

const toDateTimeLocalValue = (value: string): string => {
    if (value === '') {
        return '';
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');

    return normalized.slice(0, 16);
};

const getOwnerOptions = (
    itemType: 'raw_material' | 'product',
    rawMaterials: PurchaseCatalogOption[],
    products: PurchaseCatalogOption[],
): PurchaseCatalogOption[] => {
    return itemType === 'product' ? products : rawMaterials;
};

const defaultItem = (
    rawMaterials: PurchaseCatalogOption[],
    products: PurchaseCatalogOption[],
): PurchaseItemFormData => {
    const ownerOptions = getOwnerOptions('raw_material', rawMaterials, products);
    const owner = ownerOptions[0];
    const presentation = owner?.presentations[0];

    return {
        item_type: 'raw_material',
        item_id: String(owner?.id ?? ''),
        presentation_id: String(presentation?.id ?? ''),
        quantity: '',
        unit_cost: '',
        total: '',
    };
};

export default function PurchaseForm({
    title,
    description,
    submitLabel,
    action,
    method,
    suppliers,
    rawMaterials,
    products,
    itemTypes,
    statuses,
    initialValues,
}: Props) {
    const form = useForm<PurchaseFormData>({
        supplier_id: initialValues?.supplier_id ?? String(suppliers[0]?.id ?? ''),
        status: initialValues?.status ?? statuses[0] ?? 'draft',
        purchased_at: toDateTimeLocalValue(initialValues?.purchased_at ?? ''),
        notes: initialValues?.notes ?? '',
        items: initialValues?.items ?? [defaultItem(rawMaterials, products)],
    });

    const setCalculatedTotal = (index: number, quantity: string, unitCost: string): void => {
        const total = Number(quantity || '0') * Number(unitCost || '0');

        form.setData(
            'items',
            form.data.items.map((item, itemIndex) =>
                itemIndex === index
                    ? {
                          ...item,
                          quantity,
                          unit_cost: unitCost,
                          total: total === 0 ? '' : total.toFixed(2),
                      }
                    : item,
            ),
        );
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            supplier_id: Number(form.data.supplier_id),
            purchased_at: form.data.purchased_at === '' ? null : form.data.purchased_at,
            items: form.data.items.map((item) => ({
                item_type: item.item_type,
                item_id: Number(item.item_id),
                presentation_type:
                    item.item_type === 'product'
                        ? 'product_presentation'
                        : 'raw_material_presentation',
                presentation_id: Number(item.presentation_id),
                quantity: Number(item.quantity),
                unit_cost: Number(item.unit_cost),
                total: Number(item.total),
            })),
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
                    <Label htmlFor="supplier_id" className="font-semibold text-nopal-700">
                        Proveedor
                    </Label>
                    <Select
                        value={form.data.supplier_id}
                        onValueChange={(value) => form.setData('supplier_id', value)}
                    >
                        <SelectTrigger id="supplier_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un proveedor" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {suppliers.map((supplier) => (
                                <SelectItem key={supplier.id} value={String(supplier.id)}>
                                    {supplier.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.supplier_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status" className="font-semibold text-nopal-700">
                        Estado
                    </Label>
                    <Select
                        value={form.data.status}
                        onValueChange={(value) => form.setData('status', value)}
                    >
                        <SelectTrigger id="status" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un estado" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {formatPurchaseStatus(status)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="purchased_at" className="font-semibold text-nopal-700">
                        Fecha de compra
                    </Label>
                    <DateTimePicker
                        id="purchased_at"
                        value={form.data.purchased_at}
                        onChange={(value) => form.setData('purchased_at', value)}
                    />
                    <InputError message={form.errors.purchased_at} />
                </div>

                <div className="grid gap-2 lg:col-span-2">
                    <Label htmlFor="notes" className="font-semibold text-nopal-700">
                        Notas
                    </Label>
                    <textarea
                        id="notes"
                        value={form.data.notes}
                        onChange={(event) => form.setData('notes', event.target.value)}
                        className="min-h-28 rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-900 outline-none ring-0 placeholder:text-stone-400 focus:border-nopal-300"
                        placeholder="Comentarios de compra, condiciones o referencia del proveedor"
                    />
                    <InputError message={form.errors.notes} />
                </div>
            </div>

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Detalle"
                        description="Agrega materias primas o productos con su presentacion, cantidad y costo."
                    />
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => form.setData('items', [...form.data.items, defaultItem(rawMaterials, products)])}
                    >
                        Agregar item
                    </Button>
                </div>

                <div className="space-y-4">
                    {form.data.items.map((item, index) => {
                        const ownerOptions = getOwnerOptions(item.item_type, rawMaterials, products);
                        const selectedOwner = ownerOptions.find((option) => String(option.id) === item.item_id);
                        const presentations = selectedOwner?.presentations ?? [];

                        return (
                            <div
                                key={`purchase-item-${index}`}
                                className="grid gap-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4 lg:grid-cols-[0.9fr_1.2fr_1.2fr_0.8fr_0.8fr_0.8fr_auto]"
                            >
                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Tipo</Label>
                                    <Select
                                        value={item.item_type}
                                        onValueChange={(value) => {
                                            const nextType = value as 'raw_material' | 'product';
                                            const nextOptions = getOwnerOptions(nextType, rawMaterials, products);
                                            const nextOwner = nextOptions[0];
                                            const nextPresentation = nextOwner?.presentations[0];

                                            form.setData(
                                                'items',
                                                form.data.items.map((currentItem, currentIndex) =>
                                                    currentIndex === index
                                                        ? {
                                                              ...currentItem,
                                                              item_type: nextType,
                                                              item_id: String(nextOwner?.id ?? ''),
                                                              presentation_id: String(nextPresentation?.id ?? ''),
                                                          }
                                                        : currentItem,
                                                ),
                                            );
                                        }}
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                            <SelectValue placeholder="Selecciona un tipo" />
                                        </SelectTrigger>
                                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                            {itemTypes.map((itemType) => (
                                                <SelectItem key={itemType} value={itemType}>
                                                    {formatPresentationOwnerType(itemType)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors[`items.${index}.item_type`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Item</Label>
                                    <Select
                                        value={item.item_id}
                                        onValueChange={(value) => {
                                            const nextOwner = ownerOptions.find((option) => String(option.id) === value);
                                            form.setData(
                                                'items',
                                                form.data.items.map((currentItem, currentIndex) =>
                                                    currentIndex === index
                                                        ? {
                                                              ...currentItem,
                                                              item_id: value,
                                                              presentation_id: String(nextOwner?.presentations[0]?.id ?? ''),
                                                          }
                                                        : currentItem,
                                                ),
                                            );
                                        }}
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                            <SelectValue placeholder="Selecciona un item" />
                                        </SelectTrigger>
                                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                            {ownerOptions.map((option) => (
                                                <SelectItem key={option.id} value={String(option.id)}>
                                                    {option.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors[`items.${index}.item_id`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Presentacion</Label>
                                    <Select
                                        value={item.presentation_id}
                                        onValueChange={(value) =>
                                            form.setData(
                                                'items',
                                                form.data.items.map((currentItem, currentIndex) =>
                                                    currentIndex === index
                                                        ? { ...currentItem, presentation_id: value }
                                                        : currentItem,
                                                ),
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                            <SelectValue placeholder="Selecciona una presentacion" />
                                        </SelectTrigger>
                                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                            {presentations.map((presentation) => (
                                                <SelectItem key={presentation.id} value={String(presentation.id)}>
                                                    {presentation.name} ({presentation.quantity} {presentation.unit.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors[`items.${index}.presentation_id`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Cantidad</Label>
                                    <Input
                                        type="number"
                                        step="0.001"
                                        min="0.001"
                                        value={item.quantity}
                                        onChange={(event) => setCalculatedTotal(index, event.target.value, item.unit_cost)}
                                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                    />
                                    <InputError message={form.errors[`items.${index}.quantity`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Costo unitario</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={item.unit_cost}
                                        onChange={(event) => setCalculatedTotal(index, item.quantity, event.target.value)}
                                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                    />
                                    <InputError message={form.errors[`items.${index}.unit_cost`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">Total</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={item.total}
                                        onChange={(event) =>
                                            form.setData(
                                                'items',
                                                form.data.items.map((currentItem, currentIndex) =>
                                                    currentIndex === index
                                                        ? { ...currentItem, total: event.target.value }
                                                        : currentItem,
                                                ),
                                            )
                                        }
                                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                    />
                                    <InputError message={form.errors[`items.${index}.total`]} />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        disabled={form.data.items.length === 1}
                                        onClick={() =>
                                            form.setData(
                                                'items',
                                                form.data.items.filter((_, currentIndex) => currentIndex !== index),
                                            )
                                        }
                                    >
                                        Quitar
                                    </Button>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <InputError message={form.errors.items} />
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
