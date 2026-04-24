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
import {
    formatInventoryMovementType,
    formatPresentationOwnerType,
} from '@/lib/inventory-display';
import type { SelectOption } from '@/types';

type InventoryAdjustmentFormData = {
    warehouse_id: string;
    item_type: 'raw_material' | 'product';
    item_id: string;
    movement_type: string;
    direction: 'in' | 'out';
    quantity: string;
    unit_cost: string;
    moved_at: string;
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
    movementTypes: string[];
    directions: string[];
    initialValues?: Partial<InventoryAdjustmentFormData>;
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

export default function InventoryAdjustmentForm({
    title,
    description,
    submitLabel,
    action,
    method,
    warehouses,
    rawMaterials,
    products,
    itemTypes,
    movementTypes,
    directions,
    initialValues,
}: Props) {
    const initialItemType = (initialValues?.item_type ?? itemTypes[0] ?? 'raw_material') as 'raw_material' | 'product';
    const initialOwnerOptions = getOwnerOptions(initialItemType, rawMaterials, products);

    const form = useForm<InventoryAdjustmentFormData>({
        warehouse_id: initialValues?.warehouse_id ?? String(warehouses[0]?.id ?? ''),
        item_type: initialItemType,
        item_id: initialValues?.item_id ?? String(initialOwnerOptions[0]?.id ?? ''),
        movement_type: initialValues?.movement_type ?? movementTypes[0] ?? 'adjustment',
        direction: (initialValues?.direction ?? directions[0] ?? 'in') as 'in' | 'out',
        quantity: initialValues?.quantity ?? '',
        unit_cost: initialValues?.unit_cost ?? '',
        moved_at: toDateTimeLocalValue(initialValues?.moved_at ?? ''),
        notes: initialValues?.notes ?? '',
    });

    const ownerOptions = getOwnerOptions(form.data.item_type, rawMaterials, products);
    const directionDisabled = form.data.movement_type === 'waste';

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            warehouse_id: Number(form.data.warehouse_id),
            item_id: Number(form.data.item_id),
            direction: form.data.movement_type === 'waste' ? 'out' : form.data.direction,
            quantity: Number(form.data.quantity),
            unit_cost: form.data.unit_cost === '' ? null : Number(form.data.unit_cost),
            moved_at: form.data.moved_at,
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
                    <Label htmlFor="warehouse_id" className="font-semibold text-nopal-700">
                        Almacen
                    </Label>
                    <Select value={form.data.warehouse_id} onValueChange={(value) => form.setData('warehouse_id', value)}>
                        <SelectTrigger id="warehouse_id" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un almacen" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {warehouses.map((warehouse) => (
                                <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                    {warehouse.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.warehouse_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="movement_type" className="font-semibold text-nopal-700">
                        Tipo de movimiento
                    </Label>
                    <Select
                        value={form.data.movement_type}
                        onValueChange={(value) => {
                            form.setData((currentData) => ({
                                ...currentData,
                                movement_type: value,
                                direction: value === 'waste' ? 'out' : currentData.direction,
                            }));
                        }}
                    >
                        <SelectTrigger id="movement_type" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                            <SelectValue placeholder="Selecciona un tipo" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {movementTypes.map((movementType) => (
                                <SelectItem key={movementType} value={movementType}>
                                    {formatInventoryMovementType(movementType)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.movement_type} />
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
                    <Label htmlFor="direction" className="font-semibold text-nopal-700">
                        Direccion
                    </Label>
                    <Select
                        value={form.data.direction}
                        onValueChange={(value) => form.setData('direction', value as 'in' | 'out')}
                        disabled={directionDisabled}
                    >
                        <SelectTrigger id="direction" className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900 disabled:bg-stone-50 disabled:text-stone-500">
                            <SelectValue placeholder="Selecciona una direccion" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {directions.map((direction) => (
                                <SelectItem key={direction} value={direction}>
                                    {direction === 'in' ? 'Entrada' : 'Salida'}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {directionDisabled && <p className="text-xs text-stone-500">La merma siempre descuenta inventario.</p>}
                    <InputError message={form.errors.direction} />
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
                    <Label htmlFor="moved_at" className="font-semibold text-nopal-700">
                        Fecha y hora
                    </Label>
                    <DateTimePicker
                        id="moved_at"
                        value={form.data.moved_at}
                        onChange={(value) => form.setData('moved_at', value)}
                    />
                    <InputError message={form.errors.moved_at} />
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
                        placeholder="Motivo del ajuste, merma o detalle operativo"
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
