import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    formatInventoryMovementType,
    formatPresentationOwnerType,
    formatWarehouseType,
    inventoryDirectionBadgeClass,
    inventoryDirectionLabel,
} from '@/lib/inventory-display';
import { formatMoney } from '@/lib/money';
import { index } from '@/routes/inventory-movements';
import type { InventoryMovementRecord, InventoryStockRecord, SelectOption } from '@/types';

type PaginatedMovements = {
    data: InventoryMovementRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function InventoryMovementsIndex({
    filters,
    warehouses,
    movements,
    stockSummary,
}: {
    filters: { search?: string; warehouse?: string };
    warehouses: SelectOption[];
    movements: PaginatedMovements;
    stockSummary: InventoryStockRecord[];
}) {
    return (
        <>
            <Head title="Movimientos de inventario" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="space-y-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Movimientos de inventario"
                        description="Consulta entradas, salidas y existencias actuales por almacen e item."
                    />

                    <form
                        className="grid gap-3 md:grid-cols-[minmax(0,1fr)_240px_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);
                            const search = String(formData.get('search') ?? '');
                            const warehouse = String(formData.get('warehouse') ?? '');

                            router.get(index.url(), { search, warehouse }, { preserveState: true, replace: true });
                        }}
                    >
                        <Input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Buscar por item, almacen o tipo de movimiento"
                            className="border-stone-200 bg-white"
                        />

                        <Select defaultValue={filters.warehouse && filters.warehouse !== '' ? filters.warehouse : 'all'} name="warehouse">
                            <SelectTrigger className="h-12 w-full rounded-xl border-stone-200 bg-white text-stone-900">
                                <SelectValue placeholder="Todos los almacenes" />
                            </SelectTrigger>
                            <SelectContent className="min-w-[var(--radix-select-trigger-width)] rounded-xl border-stone-200 bg-white text-stone-900 shadow-lg">
                                <SelectItem value="all">Todos los almacenes</SelectItem>
                                {warehouses.map((warehouse) => (
                                    <SelectItem key={warehouse.id} value={String(warehouse.id)}>
                                        {warehouse.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

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
                </div>

                <div className="space-y-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        variant="small"
                        title="Existencias actuales"
                        description="Resumen de saldo acumulado por item y almacen."
                    />

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Tipo</th>
                                    <th className="px-4 py-3 font-medium">Item</th>
                                    <th className="px-4 py-3 font-medium">Almacen</th>
                                    <th className="px-4 py-3 font-medium text-right">Existencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                {stockSummary.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-6 text-center text-stone-500">
                                            No hay existencias para los filtros seleccionados.
                                        </td>
                                    </tr>
                                ) : (
                                    stockSummary.map((stock, indexKey) => (
                                        <tr
                                            key={`${stock.item_type}-${stock.item_name}-${stock.warehouse.id ?? indexKey}`}
                                            className="border-t border-stone-200"
                                        >
                                            <td className="px-4 py-3 text-stone-600">
                                                {formatPresentationOwnerType(stock.item_type)}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-stone-900">
                                                {stock.item_name}
                                            </td>
                                            <td className="px-4 py-3 text-stone-600">
                                                <div>{stock.warehouse.name}</div>
                                                <div className="text-xs text-stone-400">
                                                    {stock.warehouse.code} - {formatWarehouseType(stock.warehouse.type)}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium text-nopal-700">
                                                {stock.balance}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between border-b border-stone-200 px-6 py-4">
                        <Heading
                            variant="small"
                            title="Movimientos"
                            description="Historial reciente de entradas y salidas."
                        />
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Fecha</th>
                                    <th className="px-6 py-4 font-medium">Item</th>
                                    <th className="px-6 py-4 font-medium">Movimiento</th>
                                    <th className="px-6 py-4 font-medium">Almacen</th>
                                    <th className="px-6 py-4 font-medium">Cantidad</th>
                                    <th className="px-6 py-4 font-medium">Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                {movements.data.map((movement) => (
                                    <tr key={movement.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 text-stone-600">
                                            {new Date(movement.moved_at).toLocaleString()}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">{movement.item_name}</div>
                                            <div className="text-xs text-stone-400">
                                                {formatPresentationOwnerType(movement.item_type)}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="text-stone-700">
                                                {formatInventoryMovementType(movement.movement_type)}
                                            </div>
                                            <Badge variant="outline" className={inventoryDirectionBadgeClass(movement.direction)}>
                                                {inventoryDirectionLabel(movement.direction)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{movement.warehouse.name}</div>
                                            <div className="text-xs text-stone-400">
                                                {movement.warehouse.code}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{movement.quantity}</div>
                                            {movement.unit_cost !== null && (
                                                <div className="text-xs text-stone-400">
                                                    Costo: {formatMoney(movement.unit_cost)}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {movement.reference_label ?? 'Sin referencia'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-6 py-4">
                        <p className="text-sm text-stone-600">{movements.data.length} movimiento(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {movements.links.map((link) => (
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

InventoryMovementsIndex.layout = {
    breadcrumbs: [{ title: 'Movimientos', href: index() }],
};
