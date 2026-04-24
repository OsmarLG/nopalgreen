const resourceLabels: Record<string, string> = {
    users: 'Usuarios',
    roles: 'Roles',
    permissions: 'Permisos',
};

const actionLabels: Record<string, string> = {
    view: 'Ver',
    create: 'Crear',
    update: 'Actualizar',
    delete: 'Eliminar',
};

const groupOrder = ['users', 'roles', 'permissions', 'ungrouped'] as const;

const toTitleCase = (value: string): string => {
    return value
        .split(/[\s._-]+/)
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

export const formatRoleName = (roleName: string): string => {
    return toTitleCase(roleName);
};

export type PermissionDisplay = {
    raw: string;
    groupKey: string;
    groupLabel: string;
    title: string;
};

export const describePermission = (
    permissionName: string,
): PermissionDisplay => {
    const [resourceKey = '', actionKey = ''] = permissionName.split('.');
    const groupKey = resourceLabels[resourceKey] ? resourceKey : 'ungrouped';
    const groupLabel =
        groupKey === 'ungrouped'
            ? 'No Agrupado'
            : resourceLabels[groupKey] ?? 'No Agrupado';

    const actionLabel = actionLabels[actionKey] ?? toTitleCase(actionKey);
    const resourceLabel =
        resourceLabels[resourceKey] ?? toTitleCase(resourceKey || permissionName);

    const title = [actionLabel, resourceLabel].filter(Boolean).join(' ').trim();

    return {
        raw: permissionName,
        groupKey,
        groupLabel,
        title: title === '' ? toTitleCase(permissionName) : title,
    };
};

export const groupItemsByPermission = <T>(
    items: T[],
    resolvePermissionName: (item: T) => string,
): Array<{
    key: string;
    label: string;
    items: T[];
}> => {
    const groups = new Map<
        string,
        {
            key: string;
            label: string;
            items: T[];
        }
    >();

    items.forEach((item) => {
        const permission = describePermission(resolvePermissionName(item));

        if (!groups.has(permission.groupKey)) {
            groups.set(permission.groupKey, {
                key: permission.groupKey,
                label: permission.groupLabel,
                items: [],
            });
        }

        groups.get(permission.groupKey)?.items.push(item);
    });

    return Array.from(groups.values()).sort((left, right) => {
        return (
            groupOrder.indexOf(left.key as (typeof groupOrder)[number]) -
            groupOrder.indexOf(right.key as (typeof groupOrder)[number])
        );
    });
};
