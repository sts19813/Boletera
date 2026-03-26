<?php

namespace App\Traits;

trait RedirectsByRole
{
    protected function redirectByRole($user)
    {
        // Prioridad 1: redirección por rol
        if ($user->hasRole('admin')) {
            return redirect()->route('events.index');
        }

        if ($user->hasRole('organizer')) {
            return redirect()->route('events.index');
        }

        if ($user->hasRole('taquillero')) {
            return redirect()->route('taquilla.index');
        }

        if ($user->hasRole('scanner')) {
            return redirect('/checkin');
        }

        if ($user->hasRole('finance')) {
            return redirect()->route('admin.corte.index');
        }

        if ($user->hasRole('cumbres')) {
            return redirect()->route('admin.registrations.index');
        }
        if ($user->hasRole('viewer')) {
            return redirect()->route('admin.registrations.index');
        }

        // Prioridad 2: redirección por permisos directos
        if ($user->hasAnyPermission([
            'crear eventos',
            'editar eventos',
            'eliminar eventos',
            'configurar eventos',
        ])) {
            return redirect()->route('events.index');
        }

        if ($user->hasAnyPermission([
            'vender boletos',
            'reimprimir boletos',
        ])) {
            return redirect()->route('taquilla.index');
        }

        if ($user->hasPermissionTo('escanear boletos')) {
            return redirect('/checkin');
        }

        if ($user->hasAnyPermission([
            'ver reportes',
            'exportar reportes',
        ])) {
            return redirect()->route('admin.corte.index');
        }

        if ($user->hasPermissionTo('ver inscripciones')) {
            return redirect()->route('admin.registrations.index');
        }

        return redirect()->route('unauthorized');
    }
}
