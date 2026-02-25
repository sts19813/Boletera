<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'organizer']);
        Role::create(['name' => 'taquillero']);
        Role::create(['name' => 'scanner']);
        Role::create(['name' => 'finance']);
        Role::create(['name' => 'viewer']);
    }
}

/*

Admin (Superusuario)
Debe:
Crear roles
Crear usuarios
Asignar permisos
Configurar sistema completo

Event Manager (Organizador)organizer
Debe poder:
Crear eventos
Editar eventos
Configurar precios
Crear fases
Ver reportes de su evento

Caja / Taquilla
Debe:
Vender entradas
Reimprimir tickets
Cancelar venta (con permiso específico)
NO editar eventos

Scanner (Control de acceso)
Debe:
Escanear QR
Verificar ticket
Ver estado de acceso
NO vender
NO ver finanzas
Muy limitado. Y eso es bueno.

Finanzas / Contador
Ver reportes financieros
Exportar ventas
Ver comisiones
NO modificar eventos
 */