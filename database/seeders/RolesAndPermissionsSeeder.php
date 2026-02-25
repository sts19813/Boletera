<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        // PERMISOS
        $permissions = [
            'crear eventos',
            'editar eventos',
            'eliminar eventos',
            'configurar eventos',
            'vender boletos',
            'reimprimir boletos',
            'escanear boletos',
            'ver reportes',
            'exportar reportes',
            'ver inscripciones',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // ROLES
        $admin = Role::findByName('admin');
        $organizer = Role::findByName('organizer');
        $taquillero = Role::findByName('taquillero');
        $scanner = Role::findByName('scanner');
        $finance = Role::findByName('finance');
        $viewer = Role::findByName('viewer');

        // ASIGNAR PERMISOS
        $admin->givePermissionTo(Permission::all());

        $organizer->givePermissionTo([
            'crear eventos',
            'editar eventos',
            'configurar eventos',
            'ver reportes'
        ]);

        $taquillero->givePermissionTo([
            'vender boletos',
            'reimprimir boletos'
        ]);

        $scanner->givePermissionTo([
            'escanear boletos'
        ]);

        $finance->givePermissionTo([
            'ver reportes',
            'exportar reportes'
        ]);

        $viewer->givePermissionTo([
            'ver inscripciones'
        ]);
    }
}
