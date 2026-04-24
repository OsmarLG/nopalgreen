import { Head, usePage } from '@inertiajs/react';
import InventoryAdjustmentForm from '@/pages/inventory-adjustments/partials/inventory-adjustment-form';
import { index, update } from '@/routes/inventory-adjustments';
import type { InventoryAdjustmentRecord, SelectOption } from '@/types';

export default function EditInventoryAdjustment({
    adjustmentRecord,
    warehouses,
    rawMaterials,
    products,
    itemTypes,
    movementTypes,
    directions,
}: {
    adjustmentRecord: InventoryAdjustmentRecord;
    warehouses: SelectOption[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    itemTypes: string[];
    movementTypes: string[];
    directions: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ajuste #${adjustmentRecord.id}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <InventoryAdjustmentForm
                        title="Editar ajuste o merma"
                        description="Actualiza item, direccion, cantidad o motivo del movimiento manual."
                        submitLabel="Actualizar ajuste"
                        action={update.url(adjustmentRecord.id)}
                        method="patch"
                        warehouses={warehouses}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                        movementTypes={movementTypes}
                        directions={directions}
                        initialValues={{
                            warehouse_id: String(adjustmentRecord.warehouse_id ?? ''),
                            item_type: adjustmentRecord.item_type,
                            item_id: String(adjustmentRecord.item_id ?? ''),
                            movement_type: adjustmentRecord.movement_type,
                            direction: adjustmentRecord.direction,
                            quantity: adjustmentRecord.quantity,
                            unit_cost: adjustmentRecord.unit_cost ?? '',
                            moved_at: adjustmentRecord.moved_at,
                            notes: adjustmentRecord.notes ?? '',
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditInventoryAdjustment.layout = {
    breadcrumbs: [
        { title: 'Ajustes y mermas', href: index() },
        { title: 'Editar ajuste', href: index() },
    ],
};
