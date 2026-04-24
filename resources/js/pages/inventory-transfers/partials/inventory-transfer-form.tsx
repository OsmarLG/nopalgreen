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
import { formatPresentationOwnerType } from '@/lib/inventory-display';
import type { SelectOption } from '@/types';

type InventoryTransferFormData = {
    source_warehouse_id: string;
    destination_warehouse_id: string;
    item_type: 'raw_material' | 'product';
    item_id: string;
    quantity: string;
    unit_cost: string;
    transferred_at: string;
    notes: string;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    warehouses: SelectOption[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    itemTypes: string[];
    initialValues?: Partial<InventoryTransferFormData>;
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
    rawMaterials: SelectOption[],
    products: SelectOption[],
): SelectOption[] => {
    return itemType === 'product' ? products : rawMaterials;
};

export default function InventoryTransferForm({
    title,
    description,
    submitLabel,
    action,
    method,
    warehouses,
    rawMaterials,
    products,
    itemTypes,
    initialValues,
}: Props) {
    const initialItemType = (initialValues?.item_type ?? itemTypes[0] ?? 'raw_material') as 'raw_material' | 'product';
    const initialOwnerOptions = getOwnerOptions(initialItemType, rawMaterials, products);

    const form = useForm<InventoryTransferFormData>({
        source_warehouse_id: initialValues?.source_warehouse_id ?? String(warehouses[0]?.id ?? ''),
        destination_warehouse_id: initialValues?.destination_warehouse_id ?? String(warehouses[1]?.id ?? warehouses[0]?.id ?? ''),
        item_type: initialItemType,
        item_id: initialValues?.item_id ?? String(initialOwnerOptions[0]?.id ?? ''),
        quantity: initialValues?.quantity ?? '',
        unit_cost: initialValues?.unit_cost ?? '',
        transferred_at: toDateTimeLocalValue(initialValues?.transferred_at ?? ''),
        notes: initialValues?.notes ?? '',
    });

    const ownerOptions = getOwnerOptions(form.data.item_type, rawMaterials, products);

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            source_warehouse_id: Number(form.data.source_warehouse_id),
            destination_warehouse_id: Number(form.data.destination_warehouse_id),
            item_id: Number(form.data.item_id),
            quantity: Number(form.data.quantity),
            unit_cost: form.data.unit_cost === '' ? null : Number(form.data.unit_cost),
            transferred_at: form.data.transferred_at,
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
                    <Label htmlFor="source_warehouse_id" className="font-semibold text-nopal-700">
                        Almacen origen
                    </Label>
                    <Select value={form.data.source_warehouse_id} onValueChange={(value) => form.setData('source_warehouse_id', value)}>
                        <SelectTrigger id="source_warehouse_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un almacen origen" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {warehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.source_warehouse_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="destination_warehouse_id" className="font-semibold text-nopal-700">
                        Almacen destino
                    </Label>
                    <Select value={form.data.destination_warehouse_id} onValueChange={(value) => form.setData('destination_warehouse_id', value)}>
                        <SelectTrigger id="destination_warehouse_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un almacen destino" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {warehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.destination_warehouse_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="item_type" className="font-semibold text-nopal-700">
                        Tipo de item
                    </Label>
                    <Select
                        value={form.data.item_type}
                        onValueChange={(value) => {
                            const nextType = value as 'raw_material' | 'product';
                            const nextOptions = getOwnerOptions(nextType, rawMaterials, products);

                            form.setData((currentData) => ({
                                ...currentData,
                                item_type: nextType,
                                item_id: String(nextOptions[0]?.id ?? ''),
                            }));
                        }}
                    >
                        <SelectTrigger id="item_type" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un tipo de item" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {itemTypes.map((itemType) => (
                                <SelectItem key={itemType} value={itemType}>
                                    {formatPresentationOwnerType(itemType)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.item_type} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="item_id" className="font-semibold text-nopal-700">
                        Item
                    </Label>
                    <Select value={form.data.item_id} onValueChange={(value) => form.setData('item_id', value)}>
                        <SelectTrigger id="item_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
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
                    <InputError message={form.errors.item_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="quantity" className="font-semibold text-nopal-700">
                        Cantidad
                    </Label>
                    <Input
                        id="quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        value={form.data.quantity}
                        onChange={(event) => form.setData('quantity', event.target.value)}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.quantity} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="unit_cost" className="font-semibold text-nopal-700">
                        Costo unitario
                    </Label>
                    <Input
                        id="unit_cost"
                        type="number"
                        step="0.01"
                        min="0"
                        value={form.data.unit_cost}
                        onChange={(event) => form.setData('unit_cost', event.target.value)}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                        placeholder="Opcional"
                    />
                    <InputError message={form.errors.unit_cost} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="transferred_at" className="font-semibold text-nopal-700">
                        Fecha y hora
                    </Label>
                    <DateTimePicker
                        id="transferred_at"
                        value={form.data.transferred_at}
                        onChange={(value) => form.setData('transferred_at', value)}
                    />
                    <InputError message={form.errors.transferred_at} />
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
                        placeholder="Motivo de la transferencia o comentario operativo"
                    />
                    <InputError message={form.errors.notes} />
                </div>
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
