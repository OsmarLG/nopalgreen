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
import { activeLabel, formatRecipeItemType } from '@/lib/inventory-display';
import type { SelectOption, UnitOption } from '@/types';

type RecipeItemFormData = {
    item_type: 'raw_material' | 'product';
    item_id: string;
    quantity: string;
    unit_id: string;
};

type RecipeFormData = {
    product_id: string;
    name: string;
    version: number;
    yield_quantity: string;
    yield_unit_id: string;
    is_active: boolean;
    items: RecipeItemFormData[];
};

type RecipeFormInitialValues = Omit<RecipeFormData, 'items'> & {
    items: Array<
        RecipeItemFormData & {
            id?: number;
        }
    >;
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    products: SelectOption[];
    rawMaterials: SelectOption[];
    units: UnitOption[];
    itemTypes: string[];
    initialValues?: Partial<RecipeFormInitialValues>;
};

const defaultItem = (
    rawMaterials: SelectOption[],
    units: UnitOption[],
): RecipeItemFormData => ({
    item_type: 'raw_material',
    item_id: String(rawMaterials[0]?.id ?? ''),
    quantity: '',
    unit_id: String(units[0]?.id ?? ''),
});

export default function RecipeForm({
    title,
    description,
    submitLabel,
    action,
    method,
    products,
    rawMaterials,
    units,
    itemTypes,
    initialValues,
}: Props) {
    const form = useForm<RecipeFormData>({
        product_id: initialValues?.product_id ?? String(products[0]?.id ?? ''),
        name: initialValues?.name ?? '',
        version: initialValues?.version ?? 1,
        yield_quantity: initialValues?.yield_quantity ?? '',
        yield_unit_id: initialValues?.yield_unit_id ?? String(units[0]?.id ?? ''),
        is_active: initialValues?.is_active ?? true,
        items:
            initialValues?.items?.map((item) => ({
                item_type: item.item_type,
                item_id: String(item.item_id),
                quantity: item.quantity,
                unit_id: String(item.unit_id),
            })) ?? [defaultItem(rawMaterials, units)],
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            product_id: Number(form.data.product_id),
            yield_unit_id: Number(form.data.yield_unit_id),
            items: form.data.items.map((item) => ({
                ...item,
                item_id: Number(item.item_id),
                quantity: Number(item.quantity),
                unit_id: Number(item.unit_id),
            })),
        };

        form.transform(() => payload);

        if (method === 'patch') {
            form.patch(action, { preserveScroll: true });

            return;
        }

        form.post(action, { preserveScroll: true });
    };

    const addItem = (): void => {
        form.setData('items', [...form.data.items, defaultItem(rawMaterials, units)]);
    };

    const removeItem = (index: number): void => {
        form.setData(
            'items',
            form.data.items.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const updateItem = (
        index: number,
        values: Partial<RecipeItemFormData>,
    ): void => {
        form.setData(
            'items',
            form.data.items.map((item, itemIndex) =>
                itemIndex === index ? { ...item, ...values } : item,
            ),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-8">
            <Heading title={title} description={description} />

            <div className="grid gap-6 lg:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="product_id" className="font-semibold text-nopal-700">
                        Producto final
                    </Label>
                    <Select
                        value={form.data.product_id}
                        onValueChange={(value) => form.setData('product_id', value)}
                    >
                        <SelectTrigger
                            id="product_id"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
                            <SelectValue placeholder="Selecciona un producto" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {products.map((product) => (
                                <SelectItem key={product.id} value={String(product.id)}>
                                    {product.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.product_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="name" className="font-semibold text-nopal-700">
                        Nombre de receta
                    </Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        placeholder="Formula tortilla blanca"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="version" className="font-semibold text-nopal-700">
                        Version
                    </Label>
                    <Input
                        id="version"
                        type="number"
                        min={1}
                        value={form.data.version}
                        onChange={(event) =>
                            form.setData('version', Number(event.target.value))
                        }
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.version} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="yield_quantity" className="font-semibold text-nopal-700">
                        Rendimiento
                    </Label>
                    <Input
                        id="yield_quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        value={form.data.yield_quantity}
                        onChange={(event) =>
                            form.setData('yield_quantity', event.target.value)
                        }
                        placeholder="100"
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.yield_quantity} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="yield_unit_id" className="font-semibold text-nopal-700">
                        Unidad de rendimiento
                    </Label>
                    <Select
                        value={form.data.yield_unit_id}
                        onValueChange={(value) => form.setData('yield_unit_id', value)}
                    >
                        <SelectTrigger
                            id="yield_unit_id"
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
                    <InputError message={form.errors.yield_unit_id} />
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

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Insumos"
                        description="La receta puede consumir materias primas y productos intermedios."
                    />
                    <Button type="button" variant="outline" onClick={addItem}>
                        Agregar insumo
                    </Button>
                </div>

                <div className="space-y-4">
                    {form.data.items.map((item, index) => {
                        const ownerOptions =
                            item.item_type === 'product' ? products : rawMaterials;

                        return (
                            <div
                                key={`recipe-item-${index}`}
                                className="grid gap-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4 lg:grid-cols-[1fr_1.2fr_0.8fr_1fr_auto]"
                            >
                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">
                                        Tipo
                                    </Label>
                                    <Select
                                        value={item.item_type}
                                        onValueChange={(value) =>
                                            updateItem(index, {
                                                item_type:
                                                    value as RecipeItemFormData['item_type'],
                                                item_id: String(
                                                    (value === 'product'
                                                        ? products[0]?.id
                                                        : rawMaterials[0]?.id) ?? '',
                                                ),
                                            })
                                        }
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                            <SelectValue placeholder="Selecciona un tipo" />
                                        </SelectTrigger>
                                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                            {itemTypes.map((itemType) => (
                                                <SelectItem key={itemType} value={itemType}>
                                                    {formatRecipeItemType(itemType)}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={form.errors[`items.${index}.item_type`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">
                                        Insumo
                                    </Label>
                                    <Select
                                        value={item.item_id}
                                        onValueChange={(value) =>
                                            updateItem(index, { item_id: value })
                                        }
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                            <SelectValue placeholder="Selecciona un insumo" />
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
                                    <Label className="font-semibold text-nopal-700">
                                        Cantidad
                                    </Label>
                                    <Input
                                        type="number"
                                        step="0.001"
                                        min="0.001"
                                        value={item.quantity}
                                        onChange={(event) =>
                                            updateItem(index, { quantity: event.target.value })
                                        }
                                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                    />
                                    <InputError message={form.errors[`items.${index}.quantity`]} />
                                </div>

                                <div className="grid gap-2">
                                    <Label className="font-semibold text-nopal-700">
                                        Unidad
                                    </Label>
                                    <Select
                                        value={item.unit_id}
                                        onValueChange={(value) =>
                                            updateItem(index, { unit_id: value })
                                        }
                                    >
                                        <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
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
                                    <InputError message={form.errors[`items.${index}.unit_id`]} />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => removeItem(index)}
                                        disabled={form.data.items.length === 1}
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
                {form.recentlySuccessful && (
                    <p className="text-sm text-stone-500">Guardado.</p>
                )}
            </div>
        </form>
    );
}
