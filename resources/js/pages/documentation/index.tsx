import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/documentation';

type DocumentationEntry = {
    slug: string;
    title: string;
    summary: string;
    section: string;
    module: string;
};

type DocumentationGroups = Record<string, DocumentationEntry[]>;

export default function DocumentationIndex({
    filters,
    groups,
}: {
    filters: { search?: string };
    groups: DocumentationGroups;
}) {
    const sections = Object.entries(groups);

    return (
        <>
            <Head title="Documentacion" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Banco de documentacion"
                        description="Cada modulo tiene una guia simple de uso. Solo veras los archivos permitidos para tu rol o permisos."
                    />

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
                            placeholder="Buscar por modulo, seccion o palabra clave"
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
                </div>

                {sections.length === 0 ? (
                    <div className="rounded-[2rem] border border-stone-200 bg-white p-8 text-center text-stone-500 shadow-sm">
                        No hay archivos visibles para los filtros actuales.
                    </div>
                ) : (
                    <div className="space-y-6">
                        {sections.map(([section, entries]) => (
                            <section key={section} className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                                <Heading title={section} description={`${entries.length} archivo(s) disponibles.`} />
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    {entries.map((entry) => (
                                        <Link
                                            key={entry.slug}
                                            href={show(entry.slug)}
                                            className="rounded-[1.5rem] border border-stone-200 bg-stone-50 p-5 transition hover:border-nopal-200 hover:bg-nopal-50"
                                        >
                                            <div className="text-xs font-medium uppercase tracking-[0.18em] text-stone-400">{entry.module}</div>
                                            <h3 className="mt-3 text-lg font-semibold text-nopal-700">{entry.title}</h3>
                                            <p className="mt-2 text-sm leading-6 text-stone-600">{entry.summary}</p>
                                            <div className="mt-4 text-sm font-medium text-nopal-700">Abrir guia</div>
                                        </Link>
                                    ))}
                                </div>
                            </section>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

DocumentationIndex.layout = {
    breadcrumbs: [{ title: 'Documentacion', href: index() }],
};
