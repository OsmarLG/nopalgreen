import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPresentationOwnerType } from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/inventory-transfers';
import type { Auth, InventoryTransferRecord } from '@/types';

type PaginatedTransfers = {
    data: InventoryTransferRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function InventoryTransfersIndex({
    transfers,
    filters,
}: {
    transfers: PaginatedTransfers;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Transferencias" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Transferencias"
                            description="Mueve stock entre almacenes sin alterar la existencia global del item."
                        />

                        {auth.can.createInventoryTransfers && (
                            <Button asChild>
                                <Link href={create()}>Nueva transferencia</Link>
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
                            placeholder="Buscar por item, almacen o nota"
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
                                    <th className="px-6 py-4 font-medium">Fecha</th>
                                    <th className="px-6 py-4 font-medium">Item</th>
                                    <th className="px-6 py-4 font-medium">Origen</th>
                                    <th className="px-6 py-4 font-medium">Destino</th>
                                    <th className="px-6 py-4 font-medium">Cantidad</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {transfers.data.map((transfer) => (
                                    <tr key={transfer.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 text-stone-600">
                                            {new Date(transfer.transferred_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">{transfer.item_name}</div>
                                            <div className="text-xs text-stone-400">
                                                {formatPresentationOwnerType(transfer.item_type)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{transfer.source_warehouse.name}</div>
                                            <div className="text-xs text-stone-400">{transfer.source_warehouse.code}</div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{transfer.destination_warehouse.name}</div>
                                            <div className="text-xs text-stone-400">{transfer.destination_warehouse.code}</div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{transfer.quantity}</div>
                                            {transfer.unit_cost !== null && (
                                                <div className="text-xs text-stone-400">Costo: {formatMoney(transfer.unit_cost)}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateInventoryTransfers && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(transfer.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deleteInventoryTransfers && transfer.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar transferencia #${transfer.id}?`)) {
                                                                router.delete(destroy.url(transfer.id), {
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
                        <p className="text-sm text-stone-600">{transfers.data.length} transferencia(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {transfers.links.map((link) => (
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

InventoryTransfersIndex.layout = {
    breadcrumbs: [{ title: 'Transferencias', href: index() }],
};
