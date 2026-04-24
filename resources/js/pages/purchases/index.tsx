import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPurchaseStatus, purchaseStatusBadgeClass } from '@/lib/inventory-display';
import { create, destroy, edit, index } from '@/routes/purchases';
import type { Auth, PurchaseRecord } from '@/types';

type PaginatedPurchases = {
    data: PurchaseRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function PurchasesIndex({
    purchases,
    filters,
}: {
    purchases: PaginatedPurchases;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Compras" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Compras"
                            description="Registra abastecimiento externo de materias primas y productos."
                        />

                        {auth.can.createPurchases && (
                            <Button asChild>
                                <Link href={create()}>Nueva compra</Link>
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
                            placeholder="Buscar por folio, proveedor o estado"
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
                                    <th className="px-6 py-4 font-medium">Proveedor</th>
                                    <th className="px-6 py-4 font-medium">Fecha</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {purchases.data.map((purchase) => (
                                    <tr key={purchase.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 font-medium text-stone-900">
                                            {purchase.folio}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {purchase.supplier.name}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {purchase.purchased_at ? new Date(purchase.purchased_at).toLocaleString() : 'Sin fecha'}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={purchaseStatusBadgeClass(purchase.status)}>
                                                {formatPurchaseStatus(purchase.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updatePurchases && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(purchase.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deletePurchases && purchase.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${purchase.folio}?`)) {
                                                                router.delete(destroy.url(purchase.id), {
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
                        <p className="text-sm text-stone-600">{purchases.data.length} compra(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {purchases.links.map((link) => (
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

PurchasesIndex.layout = {
    breadcrumbs: [{ title: 'Compras', href: index() }],
};
