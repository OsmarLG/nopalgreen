import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { activeBadgeClass, activeLabel } from '@/lib/inventory-display';
import { create, destroy, edit, index, toggleActive } from '@/routes/recipes';
import type { Auth, RecipeRecord } from '@/types';

type PaginatedRecipes = {
    data: RecipeRecord[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export default function RecipesIndex({
    recipes,
    filters,
}: {
    recipes: PaginatedRecipes;
    filters: { search?: string };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;

    return (
        <>
            <Head title="Recetas" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Recetas"
                            description="Define formulas de produccion con rendimiento e insumos mixtos."
                        />

                        {auth.can.createRecipes && (
                            <Button asChild>
                                <Link href={create()}>Nueva receta</Link>
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
                            placeholder="Buscar por receta o producto final"
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
                                    <th className="px-6 py-4 font-medium">Receta</th>
                                    <th className="px-6 py-4 font-medium">Producto final</th>
                                    <th className="px-6 py-4 font-medium">Rendimiento</th>
                                    <th className="px-6 py-4 font-medium">Insumos</th>
                                    <th className="px-6 py-4 font-medium">Estado</th>
                                    <th className="px-6 py-4 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recipes.data.map((recipe) => (
                                    <tr key={recipe.id} className="border-t border-stone-200">
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">{recipe.name}</div>
                                            <div className="text-xs text-stone-400">Version {recipe.version}</div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {recipe.product.name}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {recipe.yield_quantity} {recipe.yield_unit.code}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {recipe.items_count ?? 0}
                                        </td>
                                        <td className="px-6 py-4">
                                            <Badge variant="outline" className={activeBadgeClass(recipe.is_active)}>
                                                {activeLabel(recipe.is_active)}
                                            </Badge>
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateRecipes && (
                                                    <Button asChild variant="outline">
                                                        <Link href={edit(recipe.id)}>Editar</Link>
                                                    </Button>
                                                )}
                                                {auth.can.updateRecipes && recipe.in_use && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        className="border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100 hover:text-amber-900"
                                                        onClick={() => {
                                                            if (confirm(`${recipe.is_active ? 'Desactivar' : 'Reactivar'} ${recipe.name}?`)) {
                                                                router.patch(toggleActive.url(recipe.id), {}, { preserveScroll: true });
                                                            }
                                                        }}
                                                    >
                                                        {recipe.is_active ? 'Desactivar' : 'Reactivar'}
                                                    </Button>
                                                )}
                                                {auth.can.deleteRecipes && recipe.can_delete && (
                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        onClick={() => {
                                                            if (confirm(`Eliminar ${recipe.name}?`)) {
                                                                router.delete(destroy.url(recipe.id), { preserveScroll: true });
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
                        <p className="text-sm text-stone-600">{recipes.data.length} receta(s) en esta pagina</p>
                        <div className="flex flex-wrap gap-2">
                            {recipes.links.map((link) => (
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

RecipesIndex.layout = {
    breadcrumbs: [{ title: 'Recetas', href: index() }],
};
