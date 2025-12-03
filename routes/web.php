<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Http\Middleware\AdminMiddleware;

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

    return redirect()->intended('/desarrollos');
});


Route::get('/unauthorized', function () {
    return view('unauthorized'); // <-- aquí apunta tu blade
})->name('unauthorized');



// =========================
// Rutas del panel admin
// =========================
Route::middleware(['auth', AdminMiddleware::class])
        ->group(function () {

  
});


// =========================
// Auth Routes
// =========================
require __DIR__ . '/auth.php';
