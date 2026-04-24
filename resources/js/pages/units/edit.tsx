import { Head, usePage } from '@inertiajs/react';
import UnitForm from '@/pages/units/partials/unit-form';
import { index, update } from '@/routes/units';
import type { UnitRecord } from '@/types';

export default function EditUnit({
    unitRecord,
}: {
    unitRecord: UnitRecord;
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${unitRecord.name}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <UnitForm
                        title="Editar unidad"
                        description="Actualiza nombre, codigo y precision decimal."
                        submitLabel="Actualizar unidad"
                        action={update.url(unitRecord.id)}
                        method="patch"
                        initialValues={unitRecord}
                    />
                </div>
            </div>
        </>
    );
}

EditUnit.layout = {
    breadcrumbs: [
        { title: 'Unidades', href: index() },
        { title: 'Editar unidad', href: index() },
    ],
};
