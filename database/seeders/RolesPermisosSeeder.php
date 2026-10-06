<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos del sistema
        $permisos = [
            // Dashboard
            'dashboard.ver',

            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Roles y permisos
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',

            // Catálogos
            'catalogos.ver',
            'productos.ver',
            'productos.sincronizar',
            'marcas.ver',
            'vitolas.ver',
            'capas.ver',
            'actividades.ver',

            // Rezago / cajones
            'cajones.ver',
            'cajones.registrar',
            'cajones.editar',
            'cajones.eliminar',

            // Anillado
            'anillado.ver',
            'anillado.registrar',
            'anillado.editar',

            // Llenado
            'llenado.ver',
            'llenado.registrar',
            'llenado.editar',

            // Reportes
            'reportes.ver',
            'reportes.exportar',

            // Costos empaque
            'costos-empaque.ver',

            // Configuración
            'configuracion.ver',
            'configuracion.editar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Crear roles
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $supervisor = Role::firstOrCreate(['name' => 'Supervisor']);
        $digitalizador = Role::firstOrCreate(['name' => 'Digitalizador']);
        $operador = Role::firstOrCreate(['name' => 'Operador']);

        // SuperAdmin: todos los permisos
        $superAdmin->syncPermissions(Permission::all());

        // Admin: casi todo, menos configuración crítica si después quieres limitar
        $admin->syncPermissions([
            'dashboard.ver',

            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',

            'roles.ver',

            'catalogos.ver',
            'productos.ver',
            'productos.sincronizar',
            'marcas.ver',
            'vitolas.ver',
            'capas.ver',
            'actividades.ver',

            'cajones.ver',
            'cajones.registrar',
            'cajones.editar',
            'cajones.eliminar',

            'anillado.ver',
            'anillado.registrar',
            'anillado.editar',

            'llenado.ver',
            'llenado.registrar',
            'llenado.editar',

            'reportes.ver',
            'reportes.exportar',

            'costos-empaque.ver',
        ]);

        // Supervisor: ver producción y reportes
        $supervisor->syncPermissions([
            'dashboard.ver',

            'catalogos.ver',
            'productos.ver',
            'marcas.ver',
            'vitolas.ver',
            'capas.ver',
            'actividades.ver',

            'cajones.ver',

            'anillado.ver',
            'llenado.ver',

            'reportes.ver',
            'reportes.exportar',
        ]);

        // Digitalizador: puede editar, eliminar, sincronizar empleados y viñetas
        $digitalizador->syncPermissions([
            'dashboard.ver',

            'catalogos.ver',
            'productos.ver',
            'productos.sincronizar',
            'marcas.ver',
            'vitolas.ver',
            'capas.ver',
            'actividades.ver',

            'cajones.ver',
            'cajones.registrar',
            'cajones.editar',
            'cajones.eliminar',

            'anillado.ver',
            'anillado.registrar',
            'anillado.editar',

            'llenado.ver',
            'llenado.registrar',
            'llenado.editar',

            'reportes.ver',
            'reportes.exportar',

            'costos-empaque.ver',
        ]);

        // Operador: registrar anillado y llenado
        $operador->syncPermissions([
            'dashboard.ver',

            'productos.ver',
            'cajones.ver',

            'anillado.ver',
            'anillado.registrar',

            'llenado.ver',
            'llenado.registrar',
        ]);
    }
}