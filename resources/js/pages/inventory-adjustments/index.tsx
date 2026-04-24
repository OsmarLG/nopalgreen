import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    formatInventoryMovementType,
    formatPresentationOwnerType,
    inventoryDirectionBadgeClass,
    inventoryDirectionLabel,
} from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/inventory-adjustments';
import type { Auth, InventoryAdjustmentRecord } from '@/types';

type PaginatedAdjustments = {
    data: InventoryAdjustmentRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function InventoryAdjustmentsIndex({
    adjustments,
    filters,
}: {
    adjustments: PaginatedAdjustments;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Ajustes y mermas" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Ajustes y mermas"
                            description="Registra correcciones manuales y mermas para mantener inventario real."
                        />

                        {auth.can.createInventoryAdjustments && (
                            <Button asChild>
                                <Link href={create()}>Nuevo ajuste</Link>
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
                            placeholder="Buscar por item, almacen, tipo o nota"
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
                                    <th className="px-6 py-4 font-medium">Movimiento</th>
                                    <th className="px-6 py-4 font-medium">Almacen</th>
                                    <th className="px-6 py-4 font-medium">Cantidad</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {adjustments.data.map((adjustment) => (
                                    <tr key={adjustment.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 text-stone-600">
                                            {new Date(adjustment.moved_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">{adjustment.item_name}</div>
                                            <div className="text-xs text-stone-400">
                                                {formatPresentationOwnerType(adjustment.item_type)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-stone-700">
                                                {formatInventoryMovementType(adjustment.movement_type)}
                                            </div>
                                            <Badge variant="outline" className={inventoryDirectionBadgeClass(adjustment.direction)}>
                                                {inventoryDirectionLabel(adjustment.direction)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {adjustment.warehouse.name}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{adjustment.quantity}</div>
                                            {adjustment.unit_cost !== null && (
                                                <div className="text-xs text-stone-400">Costo: {formatMoney(adjustment.unit_cost)}</div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateInventoryAdjustments && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(adjustment.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deleteInventoryAdjustments && adjustment.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar movimiento #${adjustment.id}?`)) {
                                                                router.delete(destroy.url(adjustment.id), {
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
                        <p className="text-sm text-stone-600">{adjustments.data.length} ajuste(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {adjustments.links.map((link) => (
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

InventoryAdjustmentsIndex.layout = {
    breadcrumbs: [{ title: 'Ajustes y mermas', href: index() }],
};
