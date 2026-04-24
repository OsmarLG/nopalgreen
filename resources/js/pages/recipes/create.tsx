import { Head } from '@inertiajs/react';
import RecipeForm from '@/pages/recipes/partials/recipe-form';
import { create, index, store } from '@/routes/recipes';
import type { SelectOption, UnitOption } from '@/types';

export default function CreateRecipe({
    products,
    rawMaterials,
    units,
    itemTypes,
}: {
    products: SelectOption[];
    rawMaterials: SelectOption[];
    units: UnitOption[];
    itemTypes: string[];
}) {
    return (
        <>
            <Head title="Nueva receta" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <RecipeForm
                        title="Crear receta"
                        description="Configura rendimiento, version e insumos de la formula de produccion."
                        submitLabel="Guardar receta"
                        action={store.url()}
                        method="post"
                        products={products}
                        rawMaterials={rawMaterials}
                        units={units}
                        itemTypes={itemTypes}
                    />
                </div>
            </div>
        </>
    );
}

CreateRecipe.layout = {
    breadcrumbs: [
        { title: 'Recetas', href: index() },
        { title: 'Nueva receta', href: create() },
    ],
};
