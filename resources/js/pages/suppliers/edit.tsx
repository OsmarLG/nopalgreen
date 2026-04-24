import { Head, usePage } from '@inertiajs/react';
import SupplierForm from '@/pages/suppliers/partials/supplier-form';
import { index, update } from '@/routes/suppliers';
import type { SupplierRecord } from '@/types';

export default function EditSupplier({
    supplierRecord,
}: {
    supplierRecord: SupplierRecord;
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${supplierRecord.name}`} />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">{status}</div>
                    )}
                    <SupplierForm
                        title="Editar proveedor"
                        description="Actualiza informacion de contacto y estado del proveedor."
                        submitLabel="Actualizar proveedor"
                        action={update.url(supplierRecord.id)}
                        method="patch"
                        initialValues={{
                            name: supplierRecord.name,
                            contact_name: supplierRecord.contact_name ?? '',
                            phone: supplierRecord.phone ?? '',
                            email: supplierRecord.email ?? '',
                            address: supplierRecord.address ?? '',
                            is_active: supplierRecord.is_active,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditSupplier.layout = {
    breadcrumbs: [
        { title: 'Proveedores', href: index() },
        { title: 'Editar proveedor', href: index() },
    ],
};
