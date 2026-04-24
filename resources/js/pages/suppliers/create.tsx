import { Head } from '@inertiajs/react';
import SupplierForm from '@/pages/suppliers/partials/supplier-form';
import { create, index, store } from '@/routes/suppliers';

export default function CreateSupplier() {
    return (
        <>
            <Head title="Nuevo proveedor" />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <SupplierForm title="Crear proveedor" description="Registra proveedores externos para compras e inventario." submitLabel="Guardar proveedor" action={store.url()} method="post" />
                </div>
            </div>
        </>
    );
}

CreateSupplier.layout = {
    breadcrumbs: [
        { title: 'Proveedores', href: index() },
        { title: 'Nuevo proveedor', href: create() },
    ],
};
