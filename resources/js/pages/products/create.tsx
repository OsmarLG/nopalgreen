import { Head } from '@inertiajs/react';
import ProductForm from '@/pages/products/partials/product-form';
import { create, index, store } from '@/routes/products';
import type { SelectOption, UnitOption } from '@/types';

export default function CreateProduct({
    units,
    suppliers,
    supplySources,
    productTypes,
}: {
    units: UnitOption[];
    suppliers: SelectOption[];
    supplySources: string[];
    productTypes: string[];
}) {
    return (
        <>
            <Head title="Nuevo producto" />
            <div className="space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <ProductForm title="Crear producto" description="Registra productos terminados o intermedios para abastecimiento y produccion." submitLabel="Guardar producto" action={store.url()} method="post" units={units} suppliers={suppliers} supplySources={supplySources} productTypes={productTypes} />
                </div>
            </div>
        </>
    );
}

CreateProduct.layout = {
    breadcrumbs: [
        { title: 'Productos', href: index() },
        { title: 'Nuevo producto', href: create() },
    ],
};
