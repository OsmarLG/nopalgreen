import { Head, useForm, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, update } from '@/routes/branding';
import type { Branding } from '@/types';

export default function BrandingIndex({
    branding,
}: {
    branding: Branding;
}) {
    const { status } = usePage<{ status?: string }>().props;
    const form = useForm<{
        app_name: string;
        app_tagline: string;
        logo: File | null;
    }>({
        app_name: branding.app_name,
        app_tagline: branding.app_tagline,
        logo: null,
    });

    return (
        <>
            <Head title="Marca" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Marca de la app"
                        description="Configura nombre comercial, subtitulo y logo principal del sistema."
                    />

                    <div className="mt-6 grid gap-4 md:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.9fr)]">
                        <div className="rounded-[2rem] border border-stone-200 bg-[linear-gradient(180deg,#ffffff_0%,#f5faed_100%)] p-6">
                            <p className="text-sm font-medium text-maiz-700">Vista previa</p>
                            <div className="mt-5 flex items-center gap-4 rounded-[1.75rem] border border-stone-200 bg-white p-4 shadow-sm">
                                {branding.logo_url ? (
                                    <img
                                        src={branding.logo_url}
                                        alt={branding.app_name}
                                        className="size-16 rounded-2xl border border-stone-200 bg-white object-contain p-2"
                                    />
                                ) : (
                                    <img
                                        src="/app-logo-default.svg"
                                        alt={branding.app_name}
                                        className="size-16 rounded-2xl border border-stone-200 bg-white object-contain p-2"
                                    />
                                )}
                                <div className="min-w-0">
                                    <p className="truncate text-lg font-semibold text-nopal-700">{branding.app_name}</p>
                                    <p className="truncate text-sm text-stone-600">{branding.app_tagline}</p>
                                </div>
                            </div>
                            <p className="mt-4 text-sm leading-6 text-stone-600">
                                El logo se usa en el sidebar, el encabezado y el icono superior del navegador.
                            </p>
                        </div>

                        <div className="rounded-[2rem] border border-stone-200 bg-stone-50 p-6">
                            <p className="text-sm font-medium text-stone-700">Donde se administra</p>
                            <p className="mt-3 text-sm leading-6 text-stone-600">
                                Esta pantalla pertenece a la configuracion general de la app, no al perfil personal del usuario.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        variant="small"
                        title="Datos de marca"
                        description="Actualiza los textos visibles y sube un logo para reemplazar el icono por defecto."
                    />

                    {typeof status === 'string' && status !== '' && (
                        <div className="mt-6 rounded-2xl border border-nopal-200 bg-nopal-50 px-4 py-3 text-sm text-nopal-700">
                            {status}
                        </div>
                    )}

                    <form
                        className="mt-6 space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();

                            form.post(update.url(), {
                                forceFormData: true,
                                preserveScroll: true,
                            });
                        }}
                    >
                        <div className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="app_name" className="font-medium text-stone-800">Nombre comercial</Label>
                                <Input
                                    id="app_name"
                                    className="border-stone-300 bg-white text-stone-900 placeholder:text-stone-400"
                                    value={form.data.app_name}
                                    onChange={(event) => form.setData('app_name', event.target.value)}
                                />
                                <InputError message={form.errors.app_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="app_tagline" className="font-medium text-stone-800">Subtitulo</Label>
                                <Input
                                    id="app_tagline"
                                    className="border-stone-300 bg-white text-stone-900 placeholder:text-stone-400"
                                    value={form.data.app_tagline}
                                    onChange={(event) => form.setData('app_tagline', event.target.value)}
                                />
                                <InputError message={form.errors.app_tagline} />
                            </div>
                        </div>

                        <div className="grid gap-3">
                            <Label htmlFor="logo" className="font-medium text-stone-800">Logo</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                className="h-11 border-stone-300 bg-white text-stone-700 file:text-stone-700"
                                onChange={(event) => form.setData('logo', event.target.files?.[0] ?? null)}
                            />
                            <p className="text-sm text-stone-500">
                                Si no subes logo, la app usa el isotipo actual por defecto.
                            </p>
                            <InputError message={form.errors.logo} />
                        </div>

                        <Button disabled={form.processing}>
                            Guardar marca
                        </Button>
                    </form>
                </section>
            </div>
        </>
    );
}

BrandingIndex.layout = {
    breadcrumbs: [
        {
            title: 'Marca',
            href: edit(),
        },
    ],
};
