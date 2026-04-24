import { Head } from '@inertiajs/react';
import ProductionOrderForm from '@/pages/production-orders/partials/production-order-form';
import { create, index, store } from '@/routes/production-orders';
import type { ProductionRecipeOption } from '@/types';

export default function CreateProductionOrder({
    recipes,
    statuses,
}: {
    recipes: ProductionRecipeOption[];
    statuses: string[];
}) {
    return (
        <>
            <Head title="Nueva orden de produccion" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <ProductionOrderForm
                        title="Crear orden de produccion"
                        description="Programa la produccion, precarga consumos desde la receta y registra el avance real."
                        submitLabel="Guardar orden"
                        action={store.url()}
                        method="post"
                        recipes={recipes}
                        statuses={statuses}
                    />
                </div>
            </div>
        </>
    );
}

CreateProductionOrder.layout = {
    breadcrumbs: [
        { title: 'Ordenes de produccion', href: index() },
        { title: 'Nueva orden', href: create() },
    ],
};
