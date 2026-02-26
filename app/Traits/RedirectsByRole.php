<?php

namespace App\Traits;

trait RedirectsByRole
{
    protected function redirectByRole($user)
    {
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

        return redirect()->route('unauthorized');
    }
}