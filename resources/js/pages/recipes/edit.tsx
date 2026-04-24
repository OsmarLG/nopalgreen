import { Head, usePage } from '@inertiajs/react';
import RecipeForm from '@/pages/recipes/partials/recipe-form';
import { index, update } from '@/routes/recipes';
import type { RecipeRecord, SelectOption, UnitOption } from '@/types';

export default function EditRecipe({
    recipeRecord,
    products,
    rawMaterials,
    units,
    itemTypes,
}: {
    recipeRecord: RecipeRecord;
    products: SelectOption[];
    rawMaterials: SelectOption[];
    units: UnitOption[];
    itemTypes: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${recipeRecord.name}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <RecipeForm
                        title="Editar receta"
                        description="Actualiza producto final, version, rendimiento y composicion de la receta."
                        submitLabel="Actualizar receta"
                        action={update.url(recipeRecord.id)}
                        method="patch"
                        products={products}
                        rawMaterials={rawMaterials}
                        units={units}
                        itemTypes={itemTypes}
                        initialValues={{
                            product_id: String(recipeRecord.product.id),
                            name: recipeRecord.name,
                            version: recipeRecord.version,
                            yield_quantity: recipeRecord.yield_quantity,
                            yield_unit_id: String(recipeRecord.yield_unit.id),
                            is_active: recipeRecord.is_active,
                            items:
                                recipeRecord.items?.map((item) => ({
                                    id: item.id,
                                    item_type: item.item_type,
                                    item_id: String(item.item_id),
                                    quantity: item.quantity,
                                    unit_id: String(item.unit.id),
                                })) ?? [],
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditRecipe.layout = {
    breadcrumbs: [
        { title: 'Recetas', href: index() },
        { title: 'Editar receta', href: index() },
    ],
};
