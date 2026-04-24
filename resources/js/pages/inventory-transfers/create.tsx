import { Head } from '@inertiajs/react';
import InventoryTransferForm from '@/pages/inventory-transfers/partials/inventory-transfer-form';
import { create, index, store } from '@/routes/inventory-transfers';
import type { SelectOption } from '@/types';

export default function CreateInventoryTransfer({
    warehouses,
    rawMaterials,
    products,
    itemTypes,
}: {
    warehouses: SelectOption[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    itemTypes: string[];
}) {
    return (
        <>
            <Head title="Nueva transferencia" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <InventoryTransferForm
                        title="Crear transferencia"
                        description="Mueve un item de un almacen origen a otro almacen destino."
                        submitLabel="Guardar transferencia"
                        action={store.url()}
                        method="post"
                        warehouses={warehouses}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                    />
                </div>
            </div>
        </>
    );
}

CreateInventoryTransfer.layout = {
    breadcrumbs: [
        { title: 'Transferencias', href: index() },
        { title: 'Nueva transferencia', href: create() },
    ],
};
