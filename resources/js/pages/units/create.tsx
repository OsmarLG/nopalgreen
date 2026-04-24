import { Head } from '@inertiajs/react';
import UnitForm from '@/pages/units/partials/unit-form';
import { create, index, store } from '@/routes/units';

export default function CreateUnit() {
    return (
        <>
            <Head title="Nueva unidad" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <UnitForm
                        title="Crear unidad"
                        description="Define una unidad base reutilizable en inventario y produccion."
                        submitLabel="Guardar unidad"
                        action={store.url()}
                        method="post"
                    />
                </div>
            </div>
        </>
    );
}

CreateUnit.layout = {
    breadcrumbs: [
        { title: 'Unidades', href: index() },
        { title: 'Nueva unidad', href: create() },
    ],
};
