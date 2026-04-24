import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BarChart3, Factory, ShieldCheck, ShoppingCart, Truck } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login } from '@/routes';
import type { Auth, Branding } from '@/types';

type WelcomePageProps = {
    auth: Auth;
    branding: Branding;
};

const operations = [
    {
        title: 'Venta y POS',
        description: 'Cobro rapido, ventas por entrega y seguimiento comercial desde una sola interfaz.',
        icon: ShoppingCart,
    },
    {
        title: 'Planta',
        description: 'Recetas, ordenes de produccion, consumos y salida real de producto terminado.',
        icon: Factory,
    },
    {
        title: 'Reparto',
        description: 'Asignacion de pedidos, liquidacion de entrega y control por repartidor.',
        icon: Truck,
    },
];

const indicators = [
    { label: 'Modulos conectados', value: '10+' },
    { label: 'Roles operativos', value: '5' },
    { label: 'Vista por permiso', value: '100%' },
];

export default function Welcome() {
    const { auth, branding } = usePage<WelcomePageProps>().props;

    return (
        <>
            <Head title="Inicio">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=outfit:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,#fff3b3_0%,#fff9e8_20%,#fffdfa_45%,#ffffff_100%)] text-stone-900">
                <div className="absolute inset-x-0 top-0 h-72 bg-[linear-gradient(135deg,rgba(79,138,45,0.08),rgba(244,196,48,0.16),transparent_72%)]" />
                <div className="absolute right-[-6rem] top-24 h-72 w-72 rounded-full bg-maiz-100/50 blur-3xl" />
                <div className="absolute left-[-4rem] top-64 h-80 w-80 rounded-full bg-nopal-100/40 blur-3xl" />

                <div className="relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-6 pb-12 pt-6 lg:px-10 lg:pb-16">
                    <header className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex items-center gap-4">
                            <div className="flex h-16 w-16 items-center justify-center rounded-[1.75rem] border border-nopal-100 bg-white shadow-lg shadow-nopal-100/50">
                                <AppLogoIcon className="size-11" />
                            </div>

                            <div>
                                <p className="font-['Outfit'] text-2xl font-semibold text-nopal-700">{branding.app_name}</p>
                                <p className="text-sm uppercase tracking-[0.24em] text-maiz-700">{branding.app_tagline}</p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <div className="rounded-full border border-stone-200 bg-white/80 px-4 py-2 text-sm text-stone-600 shadow-sm backdrop-blur">
                                Operacion diaria para tortilleria, reparto y administracion
                            </div>
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    className="inline-flex items-center gap-2 rounded-full bg-nopal-700 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-nopal-700/20 transition hover:bg-nopal-800"
                                >
                                    Ir al dashboard
                                    <ArrowRight className="size-4" />
                                </Link>
                            ) : (
                                <Link
                                    href={login()}
                                    className="inline-flex items-center gap-2 rounded-full bg-nopal-700 px-5 py-2.5 text-sm font-medium text-white shadow-lg shadow-nopal-700/20 transition hover:bg-nopal-800"
                                >
                                    Iniciar sesion
                                    <ArrowRight className="size-4" />
                                </Link>
                            )}
                        </div>
                    </header>

                    <main className="mt-10 grid flex-1 gap-8 xl:grid-cols-[1.15fr_0.85fr]">
                        <section className="rounded-[2.5rem] border border-stone-200/80 bg-white/90 p-8 shadow-[0_30px_80px_-40px_rgba(44,93,31,0.35)] backdrop-blur lg:p-12">
                            <div className="inline-flex items-center gap-2 rounded-full border border-maiz-200 bg-maiz-100/80 px-4 py-2 text-sm font-medium text-maiz-800">
                                <ShieldCheck className="size-4" />
                                Plataforma interna lista para operar
                            </div>

                            <h1 className="mt-8 max-w-4xl font-['Outfit'] text-5xl leading-[0.95] font-semibold tracking-[-0.04em] text-nopal-800 md:text-6xl xl:text-7xl">
                                Controla ventas, planta, inventario y asistencia desde un mismo sistema.
                            </h1>

                            <p className="mt-6 max-w-3xl text-base leading-8 text-stone-600 md:text-lg">
                                {branding.app_name} concentra la operacion diaria del negocio en flujos claros: punto de venta, compras, produccion,
                                reparto, finanzas, reportes y documentacion por rol para que cada persona vea solo lo que le corresponde.
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex items-center gap-2 rounded-full bg-nopal-700 px-6 py-3 font-medium text-white shadow-lg shadow-nopal-700/20 transition hover:bg-nopal-800"
                                    >
                                        Continuar al sistema
                                        <ArrowRight className="size-4" />
                                    </Link>
                                ) : (
                                    <Link
                                        href={login()}
                                        className="inline-flex items-center gap-2 rounded-full bg-nopal-700 px-6 py-3 font-medium text-white shadow-lg shadow-nopal-700/20 transition hover:bg-nopal-800"
                                    >
                                        Entrar al sistema
                                        <ArrowRight className="size-4" />
                                    </Link>
                                )}

                                <div className="inline-flex items-center rounded-full border border-stone-200 bg-white px-5 py-3 text-sm text-stone-600 shadow-sm">
                                    Roles, permisos y trazabilidad real de la operacion
                                </div>
                            </div>

                            <div className="mt-10 grid gap-4 md:grid-cols-3">
                                {indicators.map((indicator) => (
                                    <div key={indicator.label} className="rounded-[1.75rem] border border-stone-200 bg-stone-50/80 p-5">
                                        <p className="text-sm uppercase tracking-[0.18em] text-stone-400">{indicator.label}</p>
                                        <p className="mt-3 font-['Outfit'] text-3xl font-semibold text-nopal-800">{indicator.value}</p>
                                    </div>
                                ))}
                            </div>
                        </section>

                        <aside className="grid gap-5">
                            <div className="overflow-hidden rounded-[2.5rem] border border-nopal-100 bg-[linear-gradient(145deg,#17301b_0%,#2c5d1f_45%,#4f8a2d_72%,#f4c430_100%)] p-8 text-white shadow-[0_30px_80px_-40px_rgba(44,93,31,0.5)]">
                                <div className="max-w-sm">
                                    <p className="text-sm uppercase tracking-[0.22em] text-white/70">Flujo operativo</p>
                                    <h2 className="mt-4 font-['Outfit'] text-4xl leading-tight font-semibold">
                                        Una sola plataforma para cobrar, producir, repartir y auditar.
                                    </h2>
                                    <p className="mt-4 text-sm leading-7 text-white/85">
                                        La operacion no se reparte en hojas sueltas. Cada movimiento queda conectado con inventario, finanzas y reportes.
                                    </p>
                                </div>

                                <div className="mt-8 grid gap-3">
                                    {operations.map((operation) => (
                                        <div key={operation.title} className="rounded-[1.6rem] border border-white/15 bg-white/10 p-4 backdrop-blur">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-white/12">
                                                    <operation.icon className="size-5" />
                                                </div>
                                                <p className="font-medium text-white">{operation.title}</p>
                                            </div>
                                            <p className="mt-3 text-sm leading-6 text-white/80">{operation.description}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-[2.5rem] border border-stone-200 bg-white p-6 shadow-[0_20px_60px_-35px_rgba(17,24,39,0.18)]">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <p className="text-sm uppercase tracking-[0.18em] text-stone-400">Visibilidad</p>
                                        <p className="mt-2 font-['Outfit'] text-2xl font-semibold text-nopal-800">Cada rol ve solo lo necesario.</p>
                                    </div>
                                    <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-nopal-50 text-nopal-700">
                                        <BarChart3 className="size-6" />
                                    </div>
                                </div>

                                <div className="mt-6 grid gap-3">
                                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                        <p className="text-sm font-medium text-stone-900">Administracion</p>
                                        <p className="mt-1 text-sm text-stone-600">Usuarios, finanzas, reportes, inventario y configuracion general.</p>
                                    </div>
                                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                        <p className="text-sm font-medium text-stone-900">Planta y reparto</p>
                                        <p className="mt-1 text-sm text-stone-600">Ordenes, entregas, liquidacion y seguimiento operativo por persona.</p>
                                    </div>
                                    <div className="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                        <p className="text-sm font-medium text-stone-900">Empleado</p>
                                        <p className="mt-1 text-sm text-stone-600">Asistencia, codigos del dia, dispositivos detectados y documentacion visible.</p>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </main>
                </div>
            </div>
        </>
    );
}
