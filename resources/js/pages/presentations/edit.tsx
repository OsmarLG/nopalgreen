import { Head, usePage } from '@inertiajs/react';
import PresentationForm from '@/pages/presentations/partials/presentation-form';
import { index, update } from '@/routes/presentations';
import type { PresentationRecord, SelectOption, UnitOption } from '@/types';

export default function EditPresentation({
    presentationRecord,
    ownerTypes,
    rawMaterials,
    products,
    units,
}: {
    presentationRecord: PresentationRecord;
    ownerTypes: string[];
    rawMaterials: SelectOption[];
    products: SelectOption[];
    units: UnitOption[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${presentationRecord.name}`} />

            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <PresentationForm
                        title="Editar presentacion"
                        description="Actualiza nombre, cantidad, unidad y estado de la presentacion."
                        submitLabel="Actualizar presentacion"
                        action={update.url({
                            ownerType: presentationRecord.owner_type,
                            presentation: presentationRecord.id,
                        })}
                        method="patch"
                        ownerTypes={ownerTypes}
                        rawMaterials={rawMaterials}
                        products={products}
                        units={units}
                        initialValues={{
                            owner_type: presentationRecord.owner_type,
                            owner_id: String(presentationRecord.owner_id),
                            name: presentationRecord.name,
                            quantity: presentationRecord.quantity,
                            unit_id: String(presentationRecord.unit.id),
                            barcode: presentationRecord.barcode ?? '',
                            is_active: presentationRecord.is_active,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditPresentation.layout = {
    breadcrumbs: [
        { title: 'Presentaciones', href: index() },
        { title: 'Editar presentacion', href: index() },
    ],
};
