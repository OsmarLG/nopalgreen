import { Head, Link, setLayoutProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/documentation';

type DocumentationEntry = {
    slug: string;
    title: string;
    summary: string;
    section: string;
    module: string;
    html: string;
};

export default function DocumentationShow({ entry }: { entry: DocumentationEntry }) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Documentacion', href: index() },
            { title: entry.title, href: show(entry.slug) },
        ],
    });

    return (
        <>
            <Head title={entry.title} />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <Heading title={entry.title} description={entry.summary} />
                        <Button asChild variant="outline">
                            <Link href={index()}>Volver al banco</Link>
                        </Button>
                    </div>

                    <div className="mt-4 flex flex-wrap gap-2 text-xs uppercase tracking-[0.18em] text-stone-400">
                        <span>{entry.section}</span>
                        <span>{entry.module}</span>
                    </div>
                </div>

                <article
                    className="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm
                    [&_h1]:mb-4 [&_h1]:text-3xl [&_h1]:font-semibold [&_h1]:text-nopal-700
                    [&_h2]:mb-3 [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-semibold [&_h2]:text-stone-900
                    [&_h3]:mb-2 [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-stone-900
                    [&_p]:mb-4 [&_p]:leading-7 [&_p]:text-stone-700
                    [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:space-y-2 [&_ul]:pl-6 [&_ul]:text-stone-700
                    [&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:space-y-2 [&_ol]:pl-6 [&_ol]:text-stone-700
                    [&_li]:leading-7
                    [&_code]:rounded [&_code]:bg-stone-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-sm [&_code]:text-stone-900
                    [&_pre]:mb-4 [&_pre]:overflow-x-auto [&_pre]:rounded-2xl [&_pre]:bg-stone-950 [&_pre]:p-4 [&_pre]:text-sm [&_pre]:text-stone-100
                    [&_pre_code]:bg-transparent [&_pre_code]:p-0 [&_pre_code]:text-stone-100
                    [&_strong]:font-semibold [&_strong]:text-stone-900
                    [&_a]:font-medium [&_a]:text-nopal-700 [&_a]:underline"
                    dangerouslySetInnerHTML={{ __html: entry.html }}
                />
            </div>
        </>
    );
}
