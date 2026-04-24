import { Head, usePage } from '@inertiajs/react';
import CustomerForm from '@/pages/customers/partials/customer-form';
import { index, update } from '@/routes/customers';
import type { CustomerRecord } from '@/types';

export default function EditCustomer({
    customerRecord,
}: {
    customerRecord: CustomerRecord;
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${customerRecord.name}`} />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">{status}</div>
                    )}
                    <CustomerForm
                        title="Editar cliente"
                        description="Actualiza tipo, contacto y estado del cliente."
                        submitLabel="Actualizar cliente"
                        action={update.url(customerRecord.id)}
                        method="patch"
                        initialValues={{
                            name: customerRecord.name,
                            customer_type: customerRecord.customer_type ?? '',
                            phone: customerRecord.phone ?? '',
                            email: customerRecord.email ?? '',
                            address: customerRecord.address ?? '',
                            is_active: customerRecord.is_active,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditCustomer.layout = {
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: 'Editar cliente', href: index() },
    ],
};
