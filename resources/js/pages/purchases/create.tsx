import { Head } from '@inertiajs/react';
import PurchaseForm from '@/pages/purchases/partials/purchase-form';
import { create, index, store } from '@/routes/purchases';
import type { PurchaseCatalogOption, SelectOption } from '@/types';

export default function CreatePurchase({
    suppliers,
    rawMaterials,
    products,
    itemTypes,
    statuses,
}: {
    suppliers: SelectOption[];
    rawMaterials: PurchaseCatalogOption[];
    products: PurchaseCatalogOption[];
    itemTypes: string[];
    statuses: string[];
}) {
    return (
        <>
            <Head title="Nueva compra" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <PurchaseForm
                        title="Crear compra"
                        description="Registra compras de materias primas y productos externos con su presentacion y costo."
                        submitLabel="Guardar compra"
                        action={store.url()}
                        method="post"
                        suppliers={suppliers}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                        statuses={statuses}
                    />
                </div>
            </div>
        </>
    );
}

CreatePurchase.layout = {
    breadcrumbs: [
        { title: 'Compras', href: index() },
        { title: 'Nueva compra', href: create() },
    ],
};
