import { Head, usePage } from '@inertiajs/react';
import RawMaterialForm from '@/pages/raw-materials/partials/raw-material-form';
import { index, update } from '@/routes/raw-materials';
import type { RawMaterialRecord, SelectOption, UnitOption } from '@/types';

export default function EditRawMaterial({
    rawMaterialRecord,
    units,
    suppliers,
    selectedSupplierId,
}: {
    rawMaterialRecord: RawMaterialRecord;
    units: UnitOption[];
    suppliers: SelectOption[];
    selectedSupplierId?: number;
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${rawMaterialRecord.name}`} />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">{status}</div>
                    )}
                    <RawMaterialForm
                        title="Editar materia prima"
                        description="Actualiza datos base, unidad principal y proveedor asociado."
                        submitLabel="Actualizar materia prima"
                        action={update.url(rawMaterialRecord.id)}
                        method="patch"
                        units={units}
                        suppliers={suppliers}
                        initialValues={{
                            name: rawMaterialRecord.name,
                            description: rawMaterialRecord.description ?? '',
                            base_unit_id: String(rawMaterialRecord.base_unit?.id ?? ''),
                            supplier_id: selectedSupplierId ? String(selectedSupplierId) : '',
                            is_active: rawMaterialRecord.is_active,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditRawMaterial.layout = {
    breadcrumbs: [
        { title: 'Materias primas', href: index() },
        { title: 'Editar materia prima', href: index() },
    ],
};
