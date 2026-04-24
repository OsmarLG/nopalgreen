import { Head } from '@inertiajs/react';
import RawMaterialForm from '@/pages/raw-materials/partials/raw-material-form';
import { create, index, store } from '@/routes/raw-materials';
import type { SelectOption, UnitOption } from '@/types';

export default function CreateRawMaterial({
    units,
    suppliers,
}: {
    units: UnitOption[];
    suppliers: SelectOption[];
}) {
    return (
        <>
            <Head title="Nueva materia prima" />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <RawMaterialForm title="Crear materia prima" description="Registra un insumo base para compras, inventario y produccion." submitLabel="Guardar materia prima" action={store.url()} method="post" units={units} suppliers={suppliers} />
                </div>
            </div>
        </>
    );
}

CreateRawMaterial.layout = {
    breadcrumbs: [
        { title: 'Materias primas', href: index() },
        { title: 'Nueva materia prima', href: create() },
    ],
};
