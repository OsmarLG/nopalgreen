import { Head, usePage } from '@inertiajs/react';
import ProductForm from '@/pages/products/partials/product-form';
import { index, update } from '@/routes/products';
import type { ProductRecord, SelectOption, UnitOption } from '@/types';

export default function EditProduct({
    productRecord,
    units,
    suppliers,
    selectedSupplierId,
    supplySources,
    productTypes,
}: {
    productRecord: ProductRecord;
    units: UnitOption[];
    suppliers: SelectOption[];
    selectedSupplierId?: number;
    supplySources: string[];
    productTypes: string[];
}) {
    const { status } = usePage<{ status?: string }>().props;

    return (
        <>
            <Head title={`Editar ${productRecord.name}`} />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    {typeof status === 'string' && status !== '' && (
                        <div className="mb-6 rounded-full border border-nopal-200 bg-nopal-50 px-4 py-2 text-sm text-nopal-700">{status}</div>
                    )}
                    <ProductForm
                        title="Editar producto"
                        description="Actualiza origen, tipo, unidad base y proveedor del producto."
                        submitLabel="Actualizar producto"
                        action={update.url(productRecord.id)}
                        method="patch"
                        units={units}
                        suppliers={suppliers}
                        supplySources={supplySources}
                        productTypes={productTypes}
                        initialValues={{
                            name: productRecord.name,
                            description: productRecord.description ?? '',
                            base_unit_id: String(productRecord.base_unit?.id ?? ''),
                            supplier_id: selectedSupplierId ? String(selectedSupplierId) : '',
                            supply_source: productRecord.supply_source,
                            product_type: productRecord.product_type,
                            sale_price: productRecord.sale_price,
                            is_active: productRecord.is_active,
                        }}
                    />
                </div>
            </div>
        </>
    );
}

EditProduct.layout = {
    breadcrumbs: [
        { title: 'Productos', href: index() },
        { title: 'Editar producto', href: index() },
    ],
};
