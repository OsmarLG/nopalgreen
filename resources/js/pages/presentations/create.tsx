import { Head } from '@inertiajs/react';
import PresentationForm from '@/pages/presentations/partials/presentation-form';
import { create, index, store } from '@/routes/presentations';
import type { SelectOption, UnitOption } from '@/types';

export default function CreatePresentation({
    ownerTypes,
    rawMaterials,
    products,
    units,
}: {
    ownerTypes: string[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    units: UnitOption[];
}) {
    return (
        <>
            <Head title="Nueva presentacion" />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <PresentationForm
                        title="Crear presentacion"
                        description="Define formatos comerciales u operativos para materias primas y productos."
                        submitLabel="Guardar presentacion"
                        action={store.url()}
                        method="post"
                        ownerTypes={ownerTypes}
                        rawMaterials={rawMaterials}
                        products={products}
                        units={units}
                    />
                </div>
            </div>
        </>
    );
}

CreatePresentation.layout = {
    breadcrumbs: [
        { title: 'Presentaciones', href: index() },
        { title: 'Nueva presentacion', href: create() },
    ],
};
