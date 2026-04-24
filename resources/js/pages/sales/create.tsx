import { Head } from '@inertiajs/react';
import SaleForm from '@/pages/sales/partials/sale-form';
import { create, index, store } from '@/routes/sales';
import type { SaleCatalogOption, SelectOption } from '@/types';

export default function CreateSale({
    customers,
    deliveryUsers,
    products,
    saleTypes,
    statuses,
}: {
    customers: SelectOption[];
    deliveryUsers: SelectOption[];
    products: SaleCatalogOption[];
    saleTypes: string[];
    statuses: string[];
}) {
    return (
        <>
            <Head title="Nueva venta" />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <SaleForm title="Crear venta" description="Registra venta directa o salida a reparto con precio final y descuento por linea." submitLabel="Guardar venta" action={store.url()} method="post" customers={customers} deliveryUsers={deliveryUsers} products={products} saleTypes={saleTypes} statuses={statuses} />
                </div>
            </div>
        </>
    );
}

CreateSale.layout = {
    breadcrumbs: [
        { title: 'Ventas', href: index() },
        { title: 'Nueva venta', href: create() },
    ],
};
