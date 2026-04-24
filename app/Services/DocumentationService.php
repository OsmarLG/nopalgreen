<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DocumentationService
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function groupedEntriesFor(User $user, ?string $search = null): array
    {
        $entries = array_values(array_filter(
            $this->visibleEntriesFor($user),
            fn (array $entry): bool => $this->matchesSearch($entry, $search),
        ));

        $grouped = [];

        foreach ($entries as $entry) {
            $grouped[$entry['section']][] = $this->formatListEntry($entry);
        }

        return $grouped;
    }

    /**
     * @return array<string, mixed>
     */
    public function findVisibleEntry(User $user, string $slug): array
    {
        $entry = collect($this->visibleEntriesFor($user))
            ->first(fn (array $item): bool => $item['slug'] === $slug);

        abort_unless(is_array($entry), 404);

        $markdownPath = resource_path('docs/'.$entry['file']);
        abort_unless(is_file($markdownPath), 404);

        return [
            ...$this->formatListEntry($entry),
            'html' => (string) Str::markdown(
                file_get_contents($markdownPath) ?: '',
                [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ],
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatListEntry(array $entry): array
    {
        return Arr::only($entry, ['slug', 'title', 'summary', 'section', 'module']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function visibleEntriesFor(User $user): array
    {
        return array_values(array_filter(
            $this->entries(),
            fn (array $entry): bool => $this->userCanViewEntry($user, $entry),
        ));
    }

    private function userCanViewEntry(User $user, array $entry): bool
    {
        $roles = $entry['roles'] ?? [];

        if (is_array($roles) && $roles !== []) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        $permissions = $entry['permissions'] ?? [];

        if (! is_array($permissions) || $permissions === []) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function matchesSearch(array $entry, ?string $search): bool
    {
        if ($search === null || trim($search) === '') {
            return true;
        }

        $haystack = Str::lower(implode(' ', [
            $entry['title'],
            $entry['summary'],
            $entry['module'],
            $entry['section'],
        ]));

        return Str::contains($haystack, Str::lower(trim($search)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function entries(): array
    {
        return [
            ['slug' => 'dashboard', 'title' => 'Dashboard', 'summary' => 'Resumen rapido por rol con lo mas importante del dia.', 'section' => 'Plataforma', 'module' => 'Dashboard', 'file' => 'dashboard.md'],
            ['slug' => 'reportes', 'title' => 'Reportes', 'summary' => 'Consulta cortes por fechas y exporta a Excel o PDF.', 'section' => 'Plataforma', 'module' => 'Reportes', 'file' => 'reportes.md', 'permissions' => ['reports.view']],
            ['slug' => 'pos', 'title' => 'POS', 'summary' => 'Captura ventas rapidas con tabs pendientes y cierre inmediato.', 'section' => 'Ventas', 'module' => 'POS', 'file' => 'pos.md', 'permissions' => ['sales.create']],
            ['slug' => 'clientes', 'title' => 'Clientes', 'summary' => 'Alta, edicion y control basico de clientes para ventas.', 'section' => 'Ventas', 'module' => 'Clientes', 'file' => 'clientes.md', 'permissions' => ['customers.view', 'customers.create', 'customers.update']],
            ['slug' => 'ventas', 'title' => 'Ventas', 'summary' => 'Venta directa y venta por entrega con repartidor.', 'section' => 'Ventas', 'module' => 'Ventas', 'file' => 'ventas.md', 'permissions' => ['sales.view', 'sales.create', 'sales.update']],
            ['slug' => 'finanzas', 'title' => 'Finanzas', 'summary' => 'Consulta ingresos, egresos, deudas y balance.', 'section' => 'Finanzas', 'module' => 'Finanzas', 'file' => 'finanzas.md', 'permissions' => ['finances.view', 'finances.create', 'finances.update']],
            ['slug' => 'compras', 'title' => 'Compras', 'summary' => 'Registra compras y recepcion de materia prima o productos.', 'section' => 'Compras', 'module' => 'Compras', 'file' => 'compras.md', 'permissions' => ['purchases.view', 'purchases.create', 'purchases.update']],
            ['slug' => 'proveedores', 'title' => 'Proveedores', 'summary' => 'Administra proveedores y su informacion de contacto.', 'section' => 'Inventario', 'module' => 'Proveedores', 'file' => 'proveedores.md', 'permissions' => ['suppliers.view', 'suppliers.create', 'suppliers.update']],
            ['slug' => 'materias-primas', 'title' => 'Materias primas', 'summary' => 'Crea insumos base y define su unidad principal.', 'section' => 'Inventario', 'module' => 'Materias primas', 'file' => 'materias-primas.md', 'permissions' => ['raw_materials.view', 'raw_materials.create', 'raw_materials.update']],
            ['slug' => 'productos', 'title' => 'Productos', 'summary' => 'Define productos de venta o intermedios con precio de venta.', 'section' => 'Inventario', 'module' => 'Productos', 'file' => 'productos.md', 'permissions' => ['products.view', 'products.create', 'products.update']],
            ['slug' => 'presentaciones', 'title' => 'Presentaciones', 'summary' => 'Configura presentaciones para compras, manejo y venta.', 'section' => 'Inventario', 'module' => 'Presentaciones', 'file' => 'presentaciones.md', 'permissions' => ['presentations.view', 'presentations.create', 'presentations.update']],
            ['slug' => 'existencias', 'title' => 'Existencias', 'summary' => 'Consulta stock actual por item y almacen.', 'section' => 'Inventario', 'module' => 'Existencias', 'file' => 'existencias.md', 'permissions' => ['inventory_movements.view']],
            ['slug' => 'ajustes-mermas', 'title' => 'Ajustes y mermas', 'summary' => 'Corrige diferencias de inventario y registra perdidas.', 'section' => 'Inventario', 'module' => 'Ajustes y mermas', 'file' => 'ajustes-mermas.md', 'permissions' => ['inventory_adjustments.view', 'inventory_adjustments.create', 'inventory_adjustments.update']],
            ['slug' => 'transferencias', 'title' => 'Transferencias', 'summary' => 'Mueve inventario entre almacenes sin alterar el total global.', 'section' => 'Inventario', 'module' => 'Transferencias', 'file' => 'transferencias.md', 'permissions' => ['inventory_transfers.view', 'inventory_transfers.create', 'inventory_transfers.update']],
            ['slug' => 'movimientos', 'title' => 'Movimientos', 'summary' => 'Revisa entradas y salidas de inventario con su origen.', 'section' => 'Inventario', 'module' => 'Movimientos', 'file' => 'movimientos.md', 'permissions' => ['inventory_movements.view']],
            ['slug' => 'recetas', 'title' => 'Recetas', 'summary' => 'Define formulas de produccion con insumos mixtos.', 'section' => 'Produccion', 'module' => 'Recetas', 'file' => 'recetas.md', 'permissions' => ['recipes.view', 'recipes.create', 'recipes.update']],
            ['slug' => 'ordenes-produccion', 'title' => 'Ordenes de produccion', 'summary' => 'Programa y cierra produccion con consumos y salidas.', 'section' => 'Produccion', 'module' => 'Ordenes de produccion', 'file' => 'ordenes-produccion.md', 'permissions' => ['production_orders.view', 'production_orders.create', 'production_orders.update']],
            ['slug' => 'usuarios', 'title' => 'Usuarios', 'summary' => 'Crea usuarios, asigna multiples roles y fecha inicial de asistencia.', 'section' => 'Configuracion', 'module' => 'Usuarios', 'file' => 'usuarios.md', 'permissions' => ['users.view', 'users.create', 'users.update']],
            ['slug' => 'roles', 'title' => 'Roles', 'summary' => 'Configura roles y permisos visuales por modulo.', 'section' => 'Configuracion', 'module' => 'Roles', 'file' => 'roles.md', 'permissions' => ['roles.view', 'roles.create', 'roles.update']],
            ['slug' => 'permisos', 'title' => 'Permisos', 'summary' => 'Consulta permisos disponibles y su uso en el sistema.', 'section' => 'Configuracion', 'module' => 'Permisos', 'file' => 'permisos.md', 'permissions' => ['permissions.view']],
            ['slug' => 'empleados', 'title' => 'Empleados', 'summary' => 'Consulta estatus de asistencia, faltas, retardos y dispositivos.', 'section' => 'Configuracion', 'module' => 'Empleados', 'file' => 'empleados.md', 'permissions' => ['employees.view']],
            ['slug' => 'unidades', 'title' => 'Unidades', 'summary' => 'Administra unidades de medida base para inventario y produccion.', 'section' => 'Configuracion', 'module' => 'Unidades', 'file' => 'unidades.md', 'permissions' => ['units.view', 'units.create', 'units.update']],
            ['slug' => 'asistencia', 'title' => 'Asistencia', 'summary' => 'Configura horario, tolerancia, dias laborales y marcado.', 'section' => 'Configuracion', 'module' => 'Asistencia', 'file' => 'asistencia.md', 'permissions' => ['attendance.manage', 'attendance.mark', 'employees.view']],
            ['slug' => 'marca', 'title' => 'Marca', 'summary' => 'Actualiza nombre comercial, subtitulo y logo principal.', 'section' => 'Configuracion', 'module' => 'Marca', 'file' => 'marca.md', 'permissions' => ['branding.update']],
        ];
    }
}
