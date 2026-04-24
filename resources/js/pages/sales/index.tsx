import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatSaleStatus, formatSaleType, saleStatusBadgeClass } from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/sales';
import type { Auth, SaleRecord } from '@/types';

type PaginatedSales = {
    data: SaleRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function SalesIndex({
    sales,
    filters,
}: {
    sales: PaginatedSales;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Ventas" />
            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading title="Ventas" description="Controla venta directa y venta por entrega con liquidacion de reparto." />
                        {auth.can.createSales && (
                            <Button asChild>
                                <Link href={create()}>Nueva venta</Link>
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
                        <Input name="search" defaultValue={filters.search ?? ''} placeholder="Buscar por folio, cliente, tipo, estado o repartidor" className="border-stone-200 bg-white md:max-w-md" />
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
                                    <th className="px-6 py-4 font-medium">Folio</th>
                                    <th className="px-6 py-4 font-medium">Cliente</th>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium">Fecha</th>
                                    <th className="px-6 py-4 font-medium">Total</th>
                                    <th className="px-6 py-4 font-medium">Repartidor</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sales.data.map((sale) => (
                                    <tr key={sale.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 font-medium text-stone-900">{sale.folio}</td>
                                        <td className="px-6 py-4 text-stone-600">{sale.customer?.name ?? 'Sin cliente'}</td>
                                        <td className="px-6 py-4 text-stone-600">{formatSaleType(sale.sale_type)}</td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={saleStatusBadgeClass(sale.status)}>
                                                {formatSaleStatus(sale.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{sale.sale_date ? new Date(sale.sale_date).toLocaleString() : 'Sin fecha'}</td>
                                        <td className="px-6 py-4 text-stone-600">{formatMoney(sale.total)}</td>
                                        <td className="px-6 py-4 text-stone-600">{sale.delivery_user?.name ?? 'Sin repartidor'}</td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateSales && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(sale.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deleteSales && sale.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${sale.folio}?`)) {
                                                                router.delete(destroy.url(sale.id), { preserveScroll: true });
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
                        <p className="text-sm text-stone-600">{sales.data.length} venta(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {sales.links.map((link) => (
                                <Button key={link.label} variant={link.active ? 'default' : 'outline'} disabled={link.url === null} onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })} dangerouslySetInnerHTML={{ __html: link.label }} />
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

SalesIndex.layout = {
    breadcrumbs: [{ title: 'Ventas', href: index() }],
};
