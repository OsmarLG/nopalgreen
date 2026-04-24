import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPresentationOwnerType, formatWarehouseType } from '@/lib/inventory-display';
import { index } from '@/routes/inventory-stocks';
import type { InventoryStockRecord, SelectOption } from '@/types';

type Props = {
    filters: { search?: string; warehouse?: string };
    warehouses: SelectOption[];
    stockSummary: InventoryStockRecord[];
    metrics: {
        records: number;
        raw_materials: number;
        products: number;
        warehouses: number;
    };
};

const metricCards = (metrics: Props['metrics']) => [
    {
        label: 'Registros con saldo',
        value: metrics.records,
        accent: 'text-nopal-700',
    },
    {
        label: 'Materias primas',
        value: metrics.raw_materials,
        accent: 'text-amber-700',
    },
    {
        label: 'Productos',
        value: metrics.products,
        accent: 'text-sky-700',
    },
    {
        label: 'Almacenes con stock',
        value: metrics.warehouses,
        accent: 'text-stone-700',
    },
];

export default function InventoryStocksIndex({ filters, warehouses, stockSummary, metrics }: Props) {
    return (
        <>
            <Head title="Existencias" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="space-y-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Existencias"
                        description="Consulta el saldo actual por materia prima, producto y almacen."
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
                            placeholder="Buscar por item o almacen"
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {metricCards(metrics).map((card) => (
                        <div
                            key={card.label}
                            className="rounded-[1.75rem] border border-stone-200 bg-gradient-to-br from-white via-[#fffdfa] to-stone-50 p-5 shadow-sm"
                        >
                            <p className="text-sm font-medium text-stone-500">{card.label}</p>
                            <p className={`mt-3 text-3xl font-semibold ${card.accent}`}>{card.value}</p>
                        </div>
                    ))}
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="border-b border-stone-200 px-6 py-4">
                        <Heading
                            variant="small"
                            title="Saldo por item"
                            description="Existencia acumulada por item y almacen a partir de los movimientos registrados."
                        />
                    </div>

                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Item</th>
                                    <th className="px-6 py-4 font-medium">Almacen</th>
                                    <th className="px-6 py-4 font-medium text-right">Existencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                {stockSummary.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-8 text-center text-stone-500">
                                            No hay existencias para los filtros seleccionados.
                                        </td>
                                    </tr>
                                ) : (
                                    stockSummary.map((stock, indexKey) => (
                                        <tr
                                            key={`${stock.item_type}-${stock.item_name}-${stock.warehouse.id ?? indexKey}`}
                                            className="border-t border-stone-200"
                                        >
                                            <td className="px-6 py-4 text-stone-600">
                                                {formatPresentationOwnerType(stock.item_type)}
                                            </td>
                                            <td className="px-6 py-4 font-medium text-stone-900">
                                                {stock.item_name}
                                            </td>
                                            <td className="px-6 py-4 text-stone-600">
                                                <div>{stock.warehouse.name}</div>
                                                <div className="text-xs text-stone-400">
                                                    {stock.warehouse.code} · {formatWarehouseType(stock.warehouse.type)}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-right text-base font-semibold text-nopal-700">
                                                {stock.balance}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

InventoryStocksIndex.layout = {
    breadcrumbs: [{ title: 'Existencias', href: index() }],
};
