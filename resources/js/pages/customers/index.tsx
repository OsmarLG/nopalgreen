import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { activeBadgeClass, activeLabel } from '@/lib/inventory-display';
import { create, destroy, edit, index, toggleActive } from '@/routes/customers';
import type { Auth, CustomerRecord } from '@/types';

type PaginatedCustomers = {
    data: CustomerRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function CustomersIndex({
    customers,
    filters,
}: {
    customers: PaginatedCustomers;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Clientes" />
            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading title="Clientes" description="Administra clientes para venta directa y entrega programada." />
                        {auth.can.createCustomers && (
                            <Button asChild>
                                <Link href={create()}>Nuevo cliente</Link>
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
                            placeholder="Buscar por nombre, tipo, telefono o email"
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
                                    <th className="px-6 py-4 font-medium">Cliente</th>
                                    <th className="px-6 py-4 font-medium">Tipo</th>
                                    <th className="px-6 py-4 font-medium">Contacto</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {customers.data.map((customer) => (
                                    <tr key={customer.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4 font-medium text-stone-900">{customer.name}</td>
                                        <td className="px-6 py-4 text-stone-600">{customer.customer_type ?? 'Sin tipo'}</td>
                                        <td className="px-6 py-4 text-stone-600">
                                            <div>{customer.phone ?? 'Sin telefono'}</div>
                                            <div className="text-xs text-stone-400">{customer.email ?? 'Sin email'}</div>
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={activeBadgeClass(customer.is_active)}>
                                                {activeLabel(customer.is_active)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateCustomers && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(customer.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.updateCustomers && customer.in_use && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 hover:text-amber-900"
                                                        onClick={() => {
                                                            if (confirm(`${customer.is_active ? 'Desactivar' : 'Reactivar'} ${customer.name}?`)) {
                                                                router.patch(toggleActive.url(customer.id), {}, { preserveScroll: true });
                                                            }
                                                        }}
                                                    >
                                                        {customer.is_active ? 'Desactivar' : 'Reactivar'}
                                                    </Button>
                                                )}
                                                {auth.can.deleteCustomers && customer.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${customer.name}?`)) {
                                                                router.delete(destroy.url(customer.id), { preserveScroll: true });
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
                        <p className="text-sm text-stone-600">{customers.data.length} cliente(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {customers.links.map((link) => (
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

CustomersIndex.layout = {
    breadcrumbs: [{ title: 'Clientes', href: index() }],
};
