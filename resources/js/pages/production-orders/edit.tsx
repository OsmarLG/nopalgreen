import { Head, usePage } from '@inertiajs/react';
import ProductionOrderForm from '@/pages/production-orders/partials/production-order-form';
import { index, update } from '@/routes/production-orders';
import type { ProductionOrderRecord, ProductionRecipeOption } from '@/types';

export default function EditProductionOrder({
    productionOrderRecord,
    recipes,
    statuses,
}: {
    productionOrderRecord: ProductionOrderRecord;
    recipes: ProductionRecipeOption[];
    statuses: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${productionOrderRecord.folio}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <ProductionOrderForm
                        title="Editar orden de produccion"
                        description="Actualiza la receta, cantidades planeadas, consumos reales y el estado operativo."
                        submitLabel="Actualizar orden"
                        action={update.url(productionOrderRecord.id)}
                        method="patch"
                        recipes={recipes}
                        statuses={statuses}
                        initialValues={{
                            recipe_id: String(productionOrderRecord.recipe.id),
                            product_id: String(productionOrderRecord.product.id),
                            planned_quantity: productionOrderRecord.planned_quantity,
                            produced_quantity: productionOrderRecord.produced_quantity,
                            unit_id: String(productionOrderRecord.unit.id),
                            status: productionOrderRecord.status,
                            scheduled_for: productionOrderRecord.scheduled_for ?? '',
                            started_at: productionOrderRecord.started_at ?? '',
                            finished_at: productionOrderRecord.finished_at ?? '',
                            notes: productionOrderRecord.notes ?? '',
                            consumptions:
                                productionOrderRecord.consumptions?.map((consumption) => ({
                                    item_type: consumption.item_type,
                                    item_id: String(consumption.item_id),
                                    item_name: consumption.item_name,
                                    planned_quantity: consumption.planned_quantity,
                                    consumed_quantity: consumption.consumed_quantity,
                                    unit_id: String(consumption.unit.id),
                                    unit_label: `${consumption.unit.name} (${consumption.unit.code})`,
                                })) ?? [],
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditProductionOrder.layout = {
    breadcrumbs: [
        { title: 'Ordenes de produccion', href: index() },
        { title: 'Editar orden', href: index() },
    ],
};
