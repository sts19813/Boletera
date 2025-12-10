<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\EventosController;
use App\Http\Controllers\View\ProjectViewController;
use App\Http\Controllers\View\PhaseViewController;
use App\Http\Controllers\View\StageViewController;
use App\Http\Controllers\View\TicketViewController;
use App\Http\Controllers\ProfileController;

Route::view('/', 'login');

// =========================
// Autenticación con Google
// =========================
Route::get('/google-auth/redirect', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/google-auth/callback', function () {
    $user_google = Socialite::driver('google')
        ->stateless()
        ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))
        ->user();

    $user = User::updateOrCreate(
        ['google_id' => $user_google->id],
        [
            'name'  => $user_google->name,
            'email' => $user_google->email,
        ]
    );

    Auth::login($user);

    return redirect()->intended('/Eventos');
});


Route::get('/unauthorized', function () {
    return view('unauthorized'); // <-- aquí apunta tu blade
})->name('unauthorized');

Route::get('/lang/{lang}', function ($lang) {
    session(['locale' => $lang]);
    return back();
})->name('lang.switch');


// =========================
// Rutas del panel admin
// =========================
Route::middleware(['auth', AdminMiddleware::class])
        ->group(function () {


        // =========================
        // CRUD Eventos
        // =========================
        Route::get('/Eventos/create', [EventosController::class, 'create'])
            ->name('events.create');

        Route::get('/events', [EventosController::class, 'index'])
            ->name('events.index');

        Route::post('/events', [EventosController::class, 'store'])
            ->name('events.store');

        Route::get('/events/{desarrollo}/edit', [EventosController::class, 'edit'])
            ->name('events.edit');

        Route::put('/events/{desarrollo}', [EventosController::class, 'update'])
            ->name('events.update');

        Route::delete('/events/{desarrollo}', [EventosController::class, 'destroy'])
            ->name('events.destroy');

        Route::get('/dashboards', [EventosController::class, 'index'])->name('dashboards.index');

        Route::get('/events/{event}/configurator', [EventosController::class, 'configurator'])
            ->name('events.configurator');

  
        //perfil
        Route::get('/perfil', [ProfileController::class, 'index'])->name('profile.index');
        Route::post('/perfil/actualizar', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
        Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');


        
        // Catálogo
        Route::get('/projects', [ProjectViewController::class, 'index'])->name('projects.index');
        Route::get('/phases', [PhaseViewController::class, 'index'])->name('phases.index');
        Route::get('/stages', [StageViewController::class, 'index'])->name('stages.index');
        Route::get('/tickets', [TicketViewController::class, 'index'])->name('tickets.index');

});


Route::get('/iframe/{lot}/', [EventosController::class, 'iframe'])
    ->name('iframe.index');

// =========================
// Auth Routes
// =========================
require __DIR__ . '/auth.php';
