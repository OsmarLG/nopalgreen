import { Head, usePage } from '@inertiajs/react';
import SaleForm from '@/pages/sales/partials/sale-form';
import { index, update } from '@/routes/sales';
import type { SaleCatalogOption, SaleRecord, SelectOption } from '@/types';

export default function EditSale({
    saleRecord,
    customers,
    deliveryUsers,
    products,
    saleTypes,
    statuses,
}: {
    saleRecord: SaleRecord;
    customers: SelectOption[];
    deliveryUsers: SelectOption[];
    products: SaleCatalogOption[];
    saleTypes: string[];
    statuses: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${saleRecord.folio}`} />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">{status}</div>
                    )}
                    <SaleForm
                        title="Editar venta"
                        description="Actualiza la captura, liquidacion y asignacion de reparto."
                        submitLabel="Actualizar venta"
                        action={update.url(saleRecord.id)}
                        method="patch"
                        customers={customers}
                        deliveryUsers={deliveryUsers}
                        products={products}
                        saleTypes={saleTypes}
                        statuses={statuses}
                        initialValues={{
                            customer_id: saleRecord.customer ? String(saleRecord.customer.id) : '',
                            delivery_user_id: saleRecord.delivery_user ? String(saleRecord.delivery_user.id) : '',
                            sale_type: saleRecord.sale_type,
                            status: saleRecord.status,
                            sale_date: saleRecord.sale_date ?? '',
                            delivery_date: saleRecord.delivery_date ?? '',
                            completed_at: saleRecord.completed_at ?? '',
                            notes: saleRecord.notes ?? '',
                            items: saleRecord.items?.map((item) => ({
                                product_id: String(item.product_id),
                                presentation_id: String(item.presentation_id ?? ''),
                                quantity: item.quantity,
                                sold_quantity: item.sold_quantity,
                                returned_quantity: item.returned_quantity,
                                catalog_price: item.catalog_price,
                                unit_price: item.unit_price,
                                discount_note: item.discount_note ?? '',
                            })) ?? [],
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditSale.layout = {
    breadcrumbs: [
        { title: 'Ventas', href: index() },
        { title: 'Editar venta', href: index() },
    ],
};
