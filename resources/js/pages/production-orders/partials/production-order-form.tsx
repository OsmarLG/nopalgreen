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
    formatProductionOrderStatus,
    formatRecipeItemType,
} from '@/lib/inventory-display';
import type { ProductionRecipeOption } from '@/types';

type ConsumptionFormData = {
    item_type: 'raw_material' | 'product';
    item_id: string;
    item_name: string;
    planned_quantity: string;
    consumed_quantity: string;
    unit_id: string;
    unit_label: string;
};

type ProductionOrderFormData = {
    recipe_id: string;
    product_id: string;
    planned_quantity: string;
    produced_quantity: string;
    unit_id: string;
    status: string;
    scheduled_for: string;
    started_at: string;
    finished_at: string;
    notes: string;
    consumptions: ConsumptionFormData[];
};

type Props = {
    title: string;
    description: string;
    submitLabel: string;
    action: string;
    method: 'post' | 'patch';
    recipes: ProductionRecipeOption[];
    statuses: string[];
    initialValues?: Partial<ProductionOrderFormData>;
};

const toDateTimeLocalValue = (value: string): string => {
    if (value === '') {
        return '';
    }

    const normalized = value.includes('T') ? value : value.replace(' ', 'T');

    return normalized.slice(0, 16);
};

const findRecipe = (
    recipes: ProductionRecipeOption[],
    recipeId: string,
): ProductionRecipeOption | undefined => {
    return recipes.find((recipe) => String(recipe.id) === recipeId);
};

const scaleRecipeConsumptions = (
    recipe: ProductionRecipeOption,
    plannedQuantity: string,
    existingConsumptions: ConsumptionFormData[] = [],
): ConsumptionFormData[] => {
    const recipeYield = Number(recipe.yield_quantity);
    const desiredQuantity = Number(plannedQuantity);
    const multiplier = recipeYield > 0 && desiredQuantity > 0 ? desiredQuantity / recipeYield : 1;

    return recipe.items.map((item) => {
        const currentConsumption = existingConsumptions.find(
            (consumption) =>
                consumption.item_type === item.item_type &&
                consumption.item_id === String(item.item_id),
        );

        return {
            item_type: item.item_type,
            item_id: String(item.item_id),
            item_name: item.item_name,
            planned_quantity: (Number(item.quantity) * multiplier).toFixed(3),
            consumed_quantity: currentConsumption?.consumed_quantity ?? '0',
            unit_id: String(item.unit.id),
            unit_label: `${item.unit.name} (${item.unit.code})`,
        };
    });
};

export default function ProductionOrderForm({
    title,
    description,
    submitLabel,
    action,
    method,
    recipes,
    statuses,
    initialValues,
}: Props) {
    const initialRecipe =
        findRecipe(recipes, initialValues?.recipe_id ?? '') ?? recipes[0];

    const form = useForm<ProductionOrderFormData>({
        recipe_id: initialValues?.recipe_id ?? String(initialRecipe?.id ?? ''),
        product_id: initialValues?.product_id ?? String(initialRecipe?.product.id ?? ''),
        planned_quantity:
            initialValues?.planned_quantity ?? initialRecipe?.yield_quantity ?? '',
        produced_quantity: initialValues?.produced_quantity ?? '0',
        unit_id: initialValues?.unit_id ?? String(initialRecipe?.yield_unit.id ?? ''),
        status: initialValues?.status ?? statuses[0] ?? 'draft',
        scheduled_for: toDateTimeLocalValue(initialValues?.scheduled_for ?? ''),
        started_at: toDateTimeLocalValue(initialValues?.started_at ?? ''),
        finished_at: toDateTimeLocalValue(initialValues?.finished_at ?? ''),
        notes: initialValues?.notes ?? '',
        consumptions:
            initialValues?.consumptions ??
            (initialRecipe
                ? scaleRecipeConsumptions(initialRecipe, initialRecipe.yield_quantity)
                : []),
    });

    const selectedRecipe = findRecipe(recipes, form.data.recipe_id);

    const syncFromRecipe = (recipeId: string, plannedQuantity: string): void => {
        const recipe = findRecipe(recipes, recipeId);

        if (!recipe) {
            return;
        }

        form.setData((currentData) => ({
            ...currentData,
            recipe_id: recipeId,
            product_id: String(recipe.product.id),
            unit_id: String(recipe.yield_unit.id),
            consumptions: scaleRecipeConsumptions(recipe, plannedQuantity, currentData.consumptions),
        }));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            ...form.data,
            product_id: Number(form.data.product_id),
            recipe_id: Number(form.data.recipe_id),
            planned_quantity: Number(form.data.planned_quantity),
            produced_quantity: Number(form.data.produced_quantity),
            unit_id: Number(form.data.unit_id),
            scheduled_for: form.data.scheduled_for === '' ? null : form.data.scheduled_for,
            started_at: form.data.started_at === '' ? null : form.data.started_at,
            finished_at: form.data.finished_at === '' ? null : form.data.finished_at,
            consumptions: form.data.consumptions.map((consumption) => ({
                item_type: consumption.item_type,
                item_id: Number(consumption.item_id),
                planned_quantity: Number(consumption.planned_quantity),
                consumed_quantity: Number(consumption.consumed_quantity),
                unit_id: Number(consumption.unit_id),
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
                    <Label htmlFor="recipe_id" className="font-semibold text-nopal-700">
                        Receta
                    </Label>
                    <Select
                        value={form.data.recipe_id}
                        onValueChange={(value) => syncFromRecipe(value, form.data.planned_quantity)}
                    >
                        <SelectTrigger
                            id="recipe_id"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
                            <SelectValue placeholder="Selecciona una receta" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {recipes.map((recipe) => (
                                <SelectItem key={recipe.id} value={String(recipe.id)}>
                                    {recipe.name} - {recipe.product.name} (V{recipe.version})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.recipe_id} />
                </div>

                <div className="grid gap-2">
                    <Label className="font-semibold text-nopal-700">Producto final</Label>
                    <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-stone-50 px-4 text-stone-700">
                        {selectedRecipe?.product.name ?? 'Sin producto'}
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="planned_quantity" className="font-semibold text-nopal-700">
                        Cantidad planeada
                    </Label>
                    <Input
                        id="planned_quantity"
                        type="number"
                        step="0.001"
                        min="0.001"
                        value={form.data.planned_quantity}
                        onChange={(event) => {
                            const value = event.target.value;
                            form.setData('planned_quantity', value);

                            if (selectedRecipe && value !== '' && Number(value) > 0) {
                                syncFromRecipe(form.data.recipe_id, value);
                            }
                        }}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.planned_quantity} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="produced_quantity" className="font-semibold text-nopal-700">
                        Cantidad producida
                    </Label>
                    <Input
                        id="produced_quantity"
                        type="number"
                        step="0.001"
                        min="0"
                        value={form.data.produced_quantity}
                        onChange={(event) => form.setData('produced_quantity', event.target.value)}
                        className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                    />
                    <InputError message={form.errors.produced_quantity} />
                </div>

                <div className="grid gap-2">
                    <Label className="font-semibold text-nopal-700">Unidad de salida</Label>
                    <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-stone-50 px-4 text-stone-700">
                        {selectedRecipe
                            ? `${selectedRecipe.yield_unit.name} (${selectedRecipe.yield_unit.code})`
                            : 'Sin unidad'}
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="status" className="font-semibold text-nopal-700">
                        Estado
                    </Label>
                    <Select
                        value={form.data.status}
                        onValueChange={(value) => form.setData('status', value)}
                    >
                        <SelectTrigger
                            id="status"
                            className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900"
                        >
                            <SelectValue placeholder="Selecciona un estado" />
                        </SelectTrigger>
                        <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                            {statuses.map((status) => (
                                <SelectItem key={status} value={status}>
                                    {formatProductionOrderStatus(status)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.status} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="scheduled_for" className="font-semibold text-nopal-700">
                        Programada para
                    </Label>
                    <DateTimePicker
                        id="scheduled_for"
                        value={form.data.scheduled_for}
                        onChange={(value) => form.setData('scheduled_for', value)}
                    />
                    <InputError message={form.errors.scheduled_for} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="started_at" className="font-semibold text-nopal-700">
                        Inicio real
                    </Label>
                    <DateTimePicker
                        id="started_at"
                        value={form.data.started_at}
                        onChange={(value) => form.setData('started_at', value)}
                    />
                    <InputError message={form.errors.started_at} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="finished_at" className="font-semibold text-nopal-700">
                        Fin real
                    </Label>
                    <DateTimePicker
                        id="finished_at"
                        value={form.data.finished_at}
                        onChange={(value) => form.setData('finished_at', value)}
                    />
                    <InputError message={form.errors.finished_at} />
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
                        placeholder="Observaciones de produccion, incidencias o comentarios del turno"
                    />
                    <InputError message={form.errors.notes} />
                </div>
            </div>

            <div className="space-y-4">
                <div className="flex items-center justify-between gap-4">
                    <Heading
                        variant="small"
                        title="Consumos"
                        description="Se precargan desde la receta y puedes registrar lo consumido realmente."
                    />
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => syncFromRecipe(form.data.recipe_id, form.data.planned_quantity)}
                    >
                        Recalcular desde receta
                    </Button>
                </div>

                <div className="space-y-4">
                    {form.data.consumptions.map((consumption, index) => (
                        <div
                            key={`consumption-${index}`}
                            className="grid gap-4 rounded-[1.5rem] border border-stone-200 bg-stone-50 p-4 lg:grid-cols-[0.8fr_1.4fr_0.9fr_0.9fr_1fr]"
                        >
                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">
                                    Tipo
                                </Label>
                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                    {formatRecipeItemType(consumption.item_type)}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">
                                    Insumo
                                </Label>
                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                    {consumption.item_name}
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">
                                    Planeado
                                </Label>
                                <Input
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    value={consumption.planned_quantity}
                                    onChange={(event) =>
                                        form.setData(
                                            'consumptions',
                                            form.data.consumptions.map((item, itemIndex) =>
                                                itemIndex === index
                                                    ? {
                                                          ...item,
                                                          planned_quantity: event.target.value,
                                                      }
                                                    : item,
                                            ),
                                        )
                                    }
                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                />
                                <InputError
                                    message={form.errors[`consumptions.${index}.planned_quantity`]}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">
                                    Consumido
                                </Label>
                                <Input
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    value={consumption.consumed_quantity}
                                    onChange={(event) =>
                                        form.setData(
                                            'consumptions',
                                            form.data.consumptions.map((item, itemIndex) =>
                                                itemIndex === index
                                                    ? {
                                                          ...item,
                                                          consumed_quantity: event.target.value,
                                                      }
                                                    : item,
                                            ),
                                        )
                                    }
                                    className="h-12 rounded-xl border-stone-200 bg-white text-stone-900"
                                />
                                <InputError
                                    message={form.errors[`consumptions.${index}.consumed_quantity`]}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label className="font-semibold text-nopal-700">
                                    Unidad
                                </Label>
                                <div className="flex h-12 items-center rounded-xl border border-stone-200 bg-white px-4 text-stone-700">
                                    {consumption.unit_label}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                <InputError message={form.errors.consumptions} />
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
