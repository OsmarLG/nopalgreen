import { Head } from '@inertiajs/react';
import InventoryAdjustmentForm from '@/pages/inventory-adjustments/partials/inventory-adjustment-form';
import { create, index, store } from '@/routes/inventory-adjustments';
import type { SelectOption } from '@/types';

export default function CreateInventoryAdjustment({
    warehouses,
    rawMaterials,
    products,
    itemTypes,
    movementTypes,
    directions,
}: {
    warehouses: SelectOption[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    itemTypes: string[];
    movementTypes: string[];
    directions: string[];
}) {
    return (
        <>
            <Head title="Nuevo ajuste" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <InventoryAdjustmentForm
                        title="Crear ajuste o merma"
                        description="Registra entradas o salidas manuales para corregir existencias reales."
                        submitLabel="Guardar ajuste"
                        action={store.url()}
                        method="post"
                        warehouses={warehouses}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                        movementTypes={movementTypes}
                        directions={directions}
                    />
                </div>
            </div>
        </>
    );
}

CreateInventoryAdjustment.layout = {
    breadcrumbs: [
        { title: 'Ajustes y mermas', href: index() },
        { title: 'Nuevo ajuste', href: create() },
    ],
};
