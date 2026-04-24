import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    describePermission,
    formatRoleName,
    groupItemsByPermission,
} from '@/lib/access-display';
import { create, destroy, edit, index } from '@/routes/users';
import type { Auth, User } from '@/types';

type PaginatedUsers = {
    data: User[];
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export default function UsersIndex({
    users,
    filters,
}: {
    users: PaginatedUsers;
    filters: {
        search?: string;
    };
}) {
    const { auth, status } = usePage<{ auth: Auth; status?: string }>().props;
    const canManageProtectedRecords =
        auth.user?.roles?.some((role) => role.name === 'master') ?? false;
    const canManageUser = (user: User): boolean =>
        !(
            user.roles?.some((role) => role.name === 'master') &&
            !canManageProtectedRecords
        );

    return (
        <>
            <Head title="Usuarios" />

            <div className="min-h-full space-y-6 bg-white p-4">
                <div className="flex flex-col gap-4 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <Heading
                            title="Usuarios"
                            description="Administra accesos internos, roles y permisos."
                        />

                        {auth.can.createUsers && (
                            <Button asChild>
                                <Link href={create()}>Nuevo usuario</Link>
                            </Button>
                        )}
                    </div>

                    <form
                        className="flex flex-col gap-3 md:flex-row"
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
                            placeholder="Buscar por nombre, username, email o rol"
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
                                    <th className="px-6 py-4 font-medium">
                                        Nombre
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Username
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Email
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Rol
                                    </th>
                                    <th className="px-6 py-4 font-medium">
                                        Permisos directos
                                    </th>
                                    <th className="px-6 py-4 font-medium text-right">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {users.data.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="border-t border-stone-200"
                                    >
                                        <td className="px-6 py-4">
                                            <div className="font-medium text-stone-900">
                                                {user.name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            @{user.username}
                                        </td>
                                        <td className="px-6 py-4 text-stone-600">
                                            {user.email}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex flex-wrap gap-2">
                                                {user.roles?.map((role) => (
                                                    <Badge key={role.id}>
                                                        {formatRoleName(role.name)}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4">
                                            {user.permissions?.length ? (
                                                <div className="space-y-3">
                                                    {groupItemsByPermission(
                                                        user.permissions,
                                                        (permission) =>
                                                            permission.name,
                                                    ).map((group) => (
                                                        <div
                                                            key={group.key}
                                                            className="space-y-2"
                                                        >
                                                            <p className="text-xs font-semibold tracking-[0.18em] text-stone-400 uppercase">
                                                                {group.label}
                                                            </p>
                                                            <div className="flex flex-wrap gap-2">
                                                                {group.items.map(
                                                                    (
                                                                        permission,
                                                                    ) => {
                                                                        const permissionDisplay =
                                                                            describePermission(
                                                                                permission.name,
                                                                            );

                                                                        return (
                                                                            <div
                                                                                key={
                                                                                    permission.id
                                                                                }
                                                                                className="min-w-40 rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2"
                                                                            >
                                                                                <p className="text-xs font-semibold text-nopal-700">
                                                                                    {
                                                                                        permissionDisplay.title
                                                                                    }
                                                                                </p>
                                                                                <p className="mt-1 text-[11px] text-stone-500">
                                                                                    {
                                                                                        permissionDisplay.raw
                                                                                    }
                                                                                </p>
                                                                            </div>
                                                                        );
                                                                    },
                                                                )}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="text-stone-500">
                                                    Sin permisos directos
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4">
                                            <div className="flex justify-end gap-2">
                                                {auth.can.updateUsers &&
                                                    canManageUser(user) && (
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={edit(user.id)}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                )}

                                                {auth.can.deleteUsers &&
                                                    canManageUser(user) && (
                                                    <Form
                                                        action={
                                                            destroy(user).url
                                                        }
                                                        method={
                                                            destroy(user).method
                                                        }
                                                        onBefore={() =>
                                                            confirm(
                                                                `Eliminar a ${user.name}?`,
                                                            )
                                                        }
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="destructive"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                Eliminar
                                                            </Button>
                                                        )}
                                                    </Form>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-stone-200 px-6 py-4">
                        <p className="text-sm text-stone-600">
                            {users.data.length} usuario(s) en esta pagina
                        </p>

                        <div className="flex flex-wrap gap-2">
                            {users.links.map((link) => (
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
                </div>
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Usuarios',
            href: index(),
        },
    ],
};
