import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { activeBadgeClass, activeLabel, formatProductType, formatSupplySource } from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index, toggleActive } from '@/routes/products';
import type { Auth, ProductRecord } from '@/types';

type PaginatedProducts = {
    data: ProductRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function ProductsIndex({
    products,
    filters,
}: {
    products: PaginatedProducts;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Productos" />
            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading title="Productos" description="Administra productos terminados o intermedios para venta y produccion." />
                        {auth.can.createProducts && (
                            <Button asChild>
                                <Link href={create()}>Nuevo producto</Link>
                            </Button>
                        )}
                    </div>
                    <form className="flex flex-col gap-3 md:flex-row" onSubmit={(event) => {
                        event.preventDefault();
                        const formData = new FormData(event.currentTarget);
                        const search = String(formData.get('search') ?? '');
                        router.get(index.url(), { search }, { preserveState: true, replace: true });
                    }}>
                        <Input name="search" defaultValue={filters.search ?? ''} placeholder="Buscar por nombre, slug o proveedor" className="border-stone-200 bg-white md:max-w-md" />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">Buscar</Button>
                            <Button type="button" variant="ghost" onClick={() => router.get(index.url(), {}, { preserveState: true, replace: true })}>Limpiar</Button>
                        </div>
                    </form>
                    {typeof status === 'string' && status !== '' && (
                        <div className="rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">{status}</div>
                    )}
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Producto</th>
                                    <th className="px-6 py-4 font-medium">Unidad</th>
                                    <th className="px-6 py-4 font-medium">Origen</th>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Precio venta</th>
                                    <th className="px-6 py-4 font-medium">Proveedor</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.data.map((product) => (
                                    <tr key={product.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">{product.name}</div>
                                            <div className="text-xs text-stone-400">{product.slug}</div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {product.base_unit?.name} ({product.base_unit?.code})
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{formatSupplySource(product.supply_source)}</td>
                                        <td className="px-6 py-4 text-stone-600">{formatProductType(product.product_type)}</td>
                                        <td className="px-6 py-4 text-stone-600">{formatMoney(product.sale_price)}</td>
                                        <td className="px-6 py-4 text-stone-600">{product.supplier_links?.[0]?.supplier.name ?? 'Sin proveedor'}</td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={activeBadgeClass(product.is_active)}>
                                                {activeLabel(product.is_active)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateProducts && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(product.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.updateProducts && product.in_use && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 hover:text-amber-900"
                                                        onClick={() => {
                                                            if (confirm(`${product.is_active ? 'Desactivar' : 'Reactivar'} ${product.name}?`)) {
                                                                router.patch(toggleActive.url(product.id), {}, { preserveScroll: true });
                                                            }
                                                        }}
                                                    >
                                                        {product.is_active ? 'Desactivar' : 'Reactivar'}
                                                    </Button>
                                                )}
                                                {auth.can.deleteProducts && product.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${product.name}?`)) {
                                                                router.delete(destroy.url(product.id), { preserveScroll: true });
                                                            }
                                                        }}
                                                    >
                                                        Eliminar
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-6 py-4">
                        <p className="text-sm text-stone-600">{products.data.length} producto(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {products.links.map((link) => (
                                <Button key={link.label} variant={link.active ? 'default' : 'outline'} disabled={link.url === null} onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })} dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

ProductsIndex.layout = {
    breadcrumbs: [{ title: 'Productos', href: index() }],
};
