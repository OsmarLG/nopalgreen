import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { Branding } from '@/types';

type DashboardCard = {
    title: string;
    value: string;
    description: string;
    tone: 'nopal' | 'maiz' | 'stone';
};

type DashboardList = {
    title: string;
    description: string;
    items: Array<{
        label: string;
        meta: string;
        status: string;
    }>;
};

const toneClasses: Record<DashboardCard['tone'], string> = {
    nopal: 'bg-[linear-gradient(180deg,#ffffff_0%,#f5faed_100%)]',
    maiz: 'bg-[linear-gradient(180deg,#ffffff_0%,#fff9e8_100%)]',
    stone: 'bg-[linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)]',
};

const statusLabel = (status: string): string => {
    return status
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

export default function Dashboard({
    roleScope,
    cards,
    lists,
}: {
    roleScope: string;
    cards: DashboardCard[];
    lists: DashboardList[];
}) {
    const { branding } = usePage<{ branding: Branding }>().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-white p-4">
                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-sm font-medium text-maiz-700">Panel operativo</p>
                            <h1 className="mt-2 text-3xl font-semibold text-nopal-700">
                                {branding.app_name}
                            </h1>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-stone-600">
                                {roleScope === 'admin' && 'Vista general de ventas, produccion, compras e inventario para administracion.'}
                                {roleScope === 'repartidor' && 'Resumen rapido de pedidos asignados y entregas cerradas de este repartidor.'}
                                {roleScope === 'planta' && 'Seguimiento operativo de ordenes y actividad de produccion en planta.'}
                                {roleScope === 'empleado' && 'Resumen personal de asistencia, codigos del dia y dispositivo con el que se esta trabajando.'}
                                {roleScope === 'general' && 'Resumen general de operacion interna con acceso segun el rol actual.'}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge className="rounded-full bg-maiz-100 px-3 py-1 text-maiz-700">
                                {branding.app_tagline}
                            </Badge>
                            <Badge
                                variant="secondary"
                                className="rounded-full bg-nopal-50 px-3 py-1 text-nopal-700"
                            >
                                Rol: {statusLabel(roleScope)}
                            </Badge>
                        </div>
                    </div>
                </section>

                <div className={`grid gap-4 ${cards.length >= 4 ? 'xl:grid-cols-4 md:grid-cols-2' : 'md:grid-cols-3'}`}>
                    {cards.map((card) => (
                        <div key={card.title} className={`rounded-[2rem] border border-stone-200 p-6 shadow-sm ${toneClasses[card.tone]}`}>
                            <p className="text-sm font-medium text-stone-500">{card.title}</p>
                            <p className="mt-4 text-4xl font-semibold text-nopal-700">{card.value}</p>
                            <p className="mt-2 text-sm text-stone-600">{card.description}</p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                    {lists.map((list) => (
                        <section key={list.title} className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <Heading variant="small" title={list.title} description={list.description} />

                            <div className="mt-5 space-y-3">
                                {list.items.length === 0 && (
                                    <div className="rounded-2xl border border-dashed border-stone-200 bg-stone-50 px-4 py-5 text-sm text-stone-500">
                                        Sin movimientos para mostrar en esta seccion.
                                    </div>
                                )}

                                {list.items.map((item, index) => (
                                    <div key={`${list.title}-${index}`} className="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p className="font-medium text-stone-900">{item.label}</p>
                                            <p className="mt-1 text-sm text-stone-500">{item.meta}</p>
                                        </div>
                                        <Badge variant="outline" className="w-fit rounded-full border-stone-200 bg-white text-stone-700">
                                            {statusLabel(item.status)}
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
