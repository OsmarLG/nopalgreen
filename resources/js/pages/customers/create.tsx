import { Head } from '@inertiajs/react';
import CustomerForm from '@/pages/customers/partials/customer-form';
import { create, index, store } from '@/routes/customers';

export default function CreateCustomer() {
    return (
        <>
            <Head title="Nuevo cliente" />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <CustomerForm
                        title="Crear cliente"
                        description="Registra clientes para venta directa o entregas programadas."
                        submitLabel="Guardar cliente"
                        action={store.url()}
                        method="post"
                    />
                </div>
            </div>
        </>
    );
}

CreateCustomer.layout = {
    breadcrumbs: [
        { title: 'Clientes', href: index() },
        { title: 'Nuevo cliente', href: create() },
    ],
};
