import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    formatProductionOrderStatus,
    productionOrderStatusBadgeClass,
} from '@/lib/inventory-display';
import { create, destroy, edit, index } from '@/routes/production-orders';
import type { Auth, ProductionOrderRecord } from '@/types';

type PaginatedProductionOrders = {
    data: ProductionOrderRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function ProductionOrdersIndex({
    productionOrders,
    filters,
}: {
    productionOrders: PaginatedProductionOrders;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Ordenes de produccion" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Ordenes de produccion"
                            description="Controla lo planeado, lo producido y los consumos reales por orden."
                        />

                        {auth.can.createProductionOrders && (
                            <Button asChild>
                                <Link href={create()}>Nueva orden</Link>
                            </Button>
                        )}
                    </div>

                    <form
                        className="flex flex-col gap-3 md:flex-row"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);
                            const search = String(formData.get('search') ?? '');

                            router.get(index.url(), { search }, { preserveState: true, replace: true });
                        }}
                    >
                        <Input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Buscar por folio, producto, receta o estado"
                            className="border-stone-200 bg-white md:max-w-md"
                        />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">
                                Buscar
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() => router.get(index.url(), {}, { preserveState: true, replace: true })}
                            >
                                Limpiar
                            </Button>
                        </div>
                    </form>

                    {typeof status === 'string' && status !== '' && (
                        <div className="rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Folio</th>
                                    <th className="px-6 py-4 font-medium">Producto</th>
                                    <th className="px-6 py-4 font-medium">Receta</th>
                                    <th className="px-6 py-4 font-medium">Planeado</th>
                                    <th className="px-6 py-4 font-medium">Producido</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {productionOrders.data.map((productionOrder) => (
                                    <tr key={productionOrder.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 font-medium text-stone-900">
                                            {productionOrder.folio}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {productionOrder.product.name}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{productionOrder.recipe.name}</div>
                                            <div className="text-xs text-stone-400">
                                                Version {productionOrder.recipe.version}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {productionOrder.planned_quantity} {productionOrder.unit.code}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {productionOrder.produced_quantity} {productionOrder.unit.code}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge
                                                variant="outline"
                                                className={productionOrderStatusBadgeClass(productionOrder.status)}
                                            >
                                                {formatProductionOrderStatus(productionOrder.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateProductionOrders && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(productionOrder.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deleteProductionOrders && productionOrder.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${productionOrder.folio}?`)) {
                                                                router.delete(destroy.url(productionOrder.id), {
                                                                    preserveScroll: true,
                                                                });
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
                        <p className="text-sm text-stone-600">
                            {productionOrders.data.length} orden(es) en esta pagina
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {productionOrders.links.map((link) => (
                                <Button
                                    key={link.label}
                                    variant={link.active ? 'default' : 'outline'}
                                    disabled={link.url === null}
                                    onClick={() => {
                                        if (link.url) {
                                            router.visit(link.url, { preserveScroll: true, preserveState: true });
                                        }
                                    }}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

ProductionOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Ordenes de produccion', href: index() }],
};
