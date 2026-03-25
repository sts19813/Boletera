<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Eventos;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        View::composer('*', function ($view) {

            if (auth()->check()) {

                $user = auth()->user();

                $events = $user->hasRole('admin')
                    ? Eventos::all()
                    : $user->events;

                $view->with('sidebarEvents', $events);
            }
        });
    }
}
