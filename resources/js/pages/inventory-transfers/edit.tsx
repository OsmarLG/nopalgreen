import { Head, usePage } from '@inertiajs/react';
import InventoryTransferForm from '@/pages/inventory-transfers/partials/inventory-transfer-form';
import { index, update } from '@/routes/inventory-transfers';
import type { InventoryTransferRecord, SelectOption } from '@/types';

export default function EditInventoryTransfer({
    transferRecord,
    warehouses,
    rawMaterials,
    products,
    itemTypes,
}: {
    transferRecord: InventoryTransferRecord;
    warehouses: SelectOption[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    itemTypes: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar transferencia #${transferRecord.id}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <InventoryTransferForm
                        title="Editar transferencia"
                        description="Actualiza almacenes, item o cantidad de la transferencia."
                        submitLabel="Actualizar transferencia"
                        action={update.url(transferRecord.id)}
                        method="patch"
                        warehouses={warehouses}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                        initialValues={{
                            source_warehouse_id: String(transferRecord.source_warehouse.id),
                            destination_warehouse_id: String(transferRecord.destination_warehouse.id),
                            item_type: transferRecord.item_type,
                            item_id: String(transferRecord.item_id ?? ''),
                            quantity: transferRecord.quantity,
                            unit_cost: transferRecord.unit_cost ?? '',
                            transferred_at: transferRecord.transferred_at,
                            notes: transferRecord.notes ?? '',
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditInventoryTransfer.layout = {
    breadcrumbs: [
        { title: 'Transferencias', href: index() },
        { title: 'Editar transferencia', href: index() },
    ],
};
