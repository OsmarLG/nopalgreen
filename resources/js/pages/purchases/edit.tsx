import { Head, usePage } from '@inertiajs/react';
import PurchaseForm from '@/pages/purchases/partials/purchase-form';
import { index, update } from '@/routes/purchases';
import type { PurchaseCatalogOption, PurchaseRecord, SelectOption } from '@/types';

export default function EditPurchase({
    purchaseRecord,
    suppliers,
    rawMaterials,
    products,
    itemTypes,
    statuses,
}: {
    purchaseRecord: PurchaseRecord;
    suppliers: SelectOption[];
    rawMaterials: PurchaseCatalogOption[];
    products: PurchaseCatalogOption[];
    itemTypes: string[];
    statuses: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${purchaseRecord.folio}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <PurchaseForm
                        title="Editar compra"
                        description="Actualiza proveedor, estado, fecha y detalle de la compra."
                        submitLabel="Actualizar compra"
                        action={update.url(purchaseRecord.id)}
                        method="patch"
                        suppliers={suppliers}
                        rawMaterials={rawMaterials}
                        products={products}
                        itemTypes={itemTypes}
                        statuses={statuses}
                        initialValues={{
                            supplier_id: String(purchaseRecord.supplier.id),
                            status: purchaseRecord.status,
                            purchased_at: purchaseRecord.purchased_at ?? '',
                            notes: purchaseRecord.notes ?? '',
                            items: purchaseRecord.items?.map((item) => ({
                                item_type: item.item_type,
                                item_id: String(item.item_id),
                                presentation_id: item.presentation_id ? String(item.presentation_id) : '',
                                quantity: item.quantity,
                                unit_cost: item.unit_cost,
                                total: item.total,
                            })) ?? [],
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditPurchase.layout = {
    breadcrumbs: [
        { title: 'Compras', href: index() },
        { title: 'Editar compra', href: index() },
    ],
};
