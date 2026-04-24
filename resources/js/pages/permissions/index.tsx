import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    describePermission,
    formatRoleName,
    groupItemsByPermission,
} from '@/lib/access-display';
import { index } from '@/routes/permissions';

type PermissionRecord = {
    id: number;
    name: string;
    roles: Array<{
        id: number;
        name: string;
    }>;
};

type PaginatedPermissions = {
    data: PermissionRecord[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export default function PermissionsIndex({
    permissions,
    filters,
}: {
    permissions: PaginatedPermissions;
    filters: {
        search?: string;
    };
}) {
    return (
        <>
            <Head title="Permisos" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <Heading
                        title="Permisos"
                        description="Consulta los permisos existentes y los roles donde se utilizan."
                    />

                    <form
                        className="mt-6 flex flex-col gap-3 md:flex-row"
                        onSubmit={(event) => {
                            event.preventDefault();

                            const formData = new FormData(event.currentTarget);
                            const search = String(formData.get('search') ?? '');

                            router.get(
                                index.url(),
                                { search },
                                { preserveState: true, replace: true },
                            );
                        }}
                    >
                        <Input
                            name="search"
                            defaultValue={filters.search ?? ''}
                            placeholder="Buscar permiso o rol relacionado"
                            className="border-stone-200 bg-white md:max-w-md"
                        />
                        <div className="flex gap-2">
                            <Button type="submit" variant="outline">
                                Buscar
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={() =>
                                    router.get(
                                        index.url(),
                                        {},
                                        { preserveState: true, replace: true },
                                    )
                                }
                            >
                                Limpiar
                            </Button>
                        </div>
                    </form>
                </div>

                <div className="space-y-6">
                    {groupItemsByPermission(
                        permissions.data,
                        (permission) => permission.name,
                    ).map((group) => (
                        <section key={group.key} className="space-y-3">
                            <div className="px-1">
                                <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">
                                    {group.label}
                                </p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                {group.items.map((permission) => {
                                    const permissionDisplay =
                                        describePermission(permission.name);

                                    return (
                                        <article
                                            key={permission.id}
                                            className="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm"
                                        >
                                            <p className="text-sm font-medium text-stone-500">
                                                Permiso
                                            </p>
                                            <h2 className="mt-3 text-xl font-semibold text-nopal-700">
                                                {permissionDisplay.title}
                                            </h2>
                                            <p className="mt-1 text-sm text-stone-500">
                                                {permissionDisplay.raw}
                                            </p>
                                            <div className="mt-5 flex flex-wrap gap-2">
                                                {permission.roles.length ? (
                                                    permission.roles.map((role) => (
                                                        <Badge
                                                            key={role.id}
                                                            variant="secondary"
                                                            className="bg-stone-100 text-stone-700"
                                                        >
                                                            {formatRoleName(
                                                                role.name,
                                                            )}
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-sm text-stone-500">
                                                        Sin roles asignados
                                                    </span>
                                                )}
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        </section>
                    ))}
                </div>

                <div className="flex flex-wrap justify-end gap-2">
                    {permissions.links.map((link) => (
                        <Button
                            key={link.label}
                            variant={link.active ? 'default' : 'outline'}
                            disabled={link.url === null}
                            onClick={() => {
                                if (link.url !== null) {
                                    router.visit(link.url, {
                                        preserveScroll: true,
                                        preserveState: true,
                                    });
                                }
                            }}
                            dangerouslySetInnerHTML={{
                                __html: link.label,
                            }}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}

PermissionsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Permisos',
            href: index(),
        },
    ],
};
