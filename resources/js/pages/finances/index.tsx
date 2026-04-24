import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { financeDirectionBadgeClass, formatFinanceSource, formatFinanceStatus, formatFinanceType } from '@/lib/finance-display';
import { formatMoney } from '@/lib/money';
import { create, destroy, edit, index } from '@/routes/finances';
import type { Auth, FinanceTransactionRecord } from '@/types';

type PaginatedFinanceTransactions = {
    data: FinanceTransactionRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function FinancesIndex({
    transactions,
    summary,
    filters,
    typeOptions,
    statusOptions,
}: {
    transactions: PaginatedFinanceTransactions;
    summary: { income: string; expense: string; balance: string; debts: string };
    filters: { search?: string; type?: string | null; status?: string | null };
    typeOptions: string[];
    statusOptions: string[];
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;
    const numericBalance = Number(summary.balance);
    const balanceCardClass = numericBalance > 0
        ? 'border-emerald-200 bg-emerald-50'
        : numericBalance < 0
            ? 'border-amber-200 bg-amber-50'
            : 'border-stone-200 bg-stone-50';
    const balanceLabelClass = numericBalance > 0
        ? 'text-emerald-700'
        : numericBalance < 0
            ? 'text-amber-700'
            : 'text-stone-600';
    const balanceValueClass = numericBalance > 0
        ? 'text-emerald-800'
        : numericBalance < 0
            ? 'text-amber-800'
            : 'text-stone-900';

    return (
        <>
            <Head title="Finanzas" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Finanzas"
                            description="Concentra ingresos, egresos, deudas, cobros, pagos, perdidas y reembolsos."
                        />

                        {auth.can.createFinances && (
                            <Button asChild>
                                <Link href={create()}>Nuevo movimiento</Link>
                            </Button>
                        )}
                    </div>

                    <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div className="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5">
                            <p className="text-sm text-emerald-700">Ingresos</p>
                            <p className="mt-2 text-3xl font-semibold text-emerald-800">{formatMoney(summary.income)}</p>
                        </div>
                        <div className="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-5">
                            <p className="text-sm text-amber-700">Egresos</p>
                            <p className="mt-2 text-3xl font-semibold text-amber-800">{formatMoney(summary.expense)}</p>
                        </div>
                        <div className={`rounded-[1.75rem] border p-5 ${balanceCardClass}`}>
                            <p className={`text-sm ${balanceLabelClass}`}>Balance</p>
                            <p className={`mt-2 text-3xl font-semibold ${balanceValueClass}`}>{formatMoney(summary.balance)}</p>
                        </div>
                        <div className="rounded-[1.75rem] border border-stone-200 bg-stone-50 p-5">
                            <p className="text-sm text-stone-600">Deudas pendientes</p>
                            <p className="mt-2 text-3xl font-semibold text-stone-900">{formatMoney(summary.debts)}</p>
                        </div>
                    </div>

                    <form
                        className="mt-6 grid gap-3 md:grid-cols-[minmax(0,1.2fr)_220px_220px_auto]"
                        onSubmit={(event) => {
                            event.preventDefault();
                            const formData = new FormData(event.currentTarget);

                            router.get(
                                index.url(),
                                {
                                    search: String(formData.get('search') ?? ''),
                                    type: String(formData.get('type') ?? ''),
                                    status: String(formData.get('status') ?? ''),
                                },
                                { preserveState: true, replace: true },
                            );
                        }}
                    >
                        <Input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Buscar por folio, concepto, detalle o fuente"
                            className="border-stone-200 bg-white"
                        />
                        <select
                            name="type"
                            defaultValue={filters.type ?? ''}
                            className="h-10 rounded-md border border-stone-200 bg-white px-3 text-sm text-stone-900"
                        >
                            <option value="">Todos los tipos</option>
                            {typeOptions.map((typeOption) => (
                                <option key={typeOption} value={typeOption}>
                                    {formatFinanceType(typeOption)}
                                </option>
                            ))}
                        </select>
                        <select
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="h-10 rounded-md border border-stone-200 bg-white px-3 text-sm text-stone-900"
                        >
                            <option value="">Todos los estados</option>
                            {statusOptions.map((statusOption) => (
                                <option key={statusOption} value={statusOption}>
                                    {formatFinanceStatus(statusOption)}
                                </option>
                            ))}
                        </select>
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">Buscar</Button>
                            <Button type="button" variant="ghost" onClick={() => router.get(index.url(), {}, { preserveState: true, replace: true })}>
                                Limpiar
                            </Button>
                        </div>
                    </form>

                    {typeof status === 'string' && status !== '' && (
                        <div className="mt-4 rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">{status}</div>
                    )}
                </div>

                <div className="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-sm">
                            <thead className="bg-stone-100 text-left text-nopal-700">
                                <tr>
                                    <th className="px-6 py-4 font-medium">Folio</th>
                                    <th className="px-6 py-4 font-medium">Concepto</th>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Fuente</th>
                                    <th className="px-6 py-4 font-medium">Monto</th>
                                    <th className="px-6 py-4 font-medium">Fecha</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.data.map((transaction) => (
                                    <tr key={transaction.id} className="border-t border-stone-200 align-top">
                                        <td className="px-6 py-4 font-medium text-stone-900">
                                            <div>{transaction.folio}</div>
                                            <div className="text-xs text-stone-400">{transaction.is_manual ? 'Manual' : 'Automatico'}</div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div className="font-medium text-stone-900">{transaction.concept}</div>
                                            {transaction.detail && <div className="mt-1 text-xs text-stone-500">{transaction.detail}</div>}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={financeDirectionBadgeClass(transaction.direction)}>
                                                {formatFinanceType(transaction.transaction_type)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">{formatFinanceSource(transaction.source)}</td>
                                        <td className="px-6 py-4 font-medium text-stone-900">{formatMoney(transaction.amount)}</td>
                                        <td className="px-6 py-4 text-stone-600">{new Date(transaction.occurred_at).toLocaleString()}</td>
                                        <td className="px-6 py-4 text-stone-600">{formatFinanceStatus(transaction.status)}</td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateFinances && transaction.can_edit && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(transaction.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.deleteFinances && transaction.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${transaction.folio}?`)) {
                                                                router.delete(destroy.url(transaction.id), { preserveScroll: true });
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
                        <p className="text-sm text-stone-600">{transactions.data.length} movimiento(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {transactions.links.map((link) => (
                                <Button
                                    key={link.label}
                                    variant={link.active ? 'default' : 'outline'}
                                    disabled={link.url === null}
                                    onClick={() => link.url && router.visit(link.url, { preserveScroll: true, preserveState: true })}
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

FinancesIndex.layout = {
    breadcrumbs: [{ title: 'Finanzas', href: index() }],
};
