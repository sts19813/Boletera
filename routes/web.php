<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CorteController;
use App\Http\Controllers\Admin\TicketReprintController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\View\ProjectViewController;
use App\Http\Controllers\View\PhaseViewController;
use App\Http\Controllers\View\StageViewController;
use App\Http\Controllers\View\TicketViewController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EventosController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketResendController;
use App\Http\Controllers\TaquillaController;
use App\Http\Controllers\WalletTestController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UnauthorizedController;
use App\Http\Controllers\LocaleController;

// =========================
// Autenticación con Google
// =========================
Route::get('/admin', [AdminController::class, 'index'])->name('admin');
Route::get('/google-auth/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/google-auth/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::get('/unauthorized', [UnauthorizedController::class, 'index'])->name('unauthorized');
Route::get('/lang/{lang}', [LocaleController::class, 'switch'])->name('lang.switch');

// =========================
// Rutas del panel admin
// =========================
Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | ADMIN (puede todo)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {

        // =========================
        // CRUD Eventos
        // =========================
        Route::get('/events/create', [EventosController::class, 'create'])->name('events.create');
        Route::get('/events', [EventosController::class, 'index'])->name('events.index');
        Route::post('/events', [EventosController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [EventosController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [EventosController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventosController::class, 'destroy'])->name('events.destroy');
        Route::delete('/events/{event}/configurator', [EventosController::class, 'destroyMapping'])->name('events.configurator.destroy');

        Route::post('/evets/fetch', action: [EventosController::class, 'fetch'])->name('events.fetch');
        Route::post('/SaveSettiingTickets', [EventosController::class, 'storeSettings'])->name('eventsSettings.store');



        // Catálogo
        Route::get('/projects', [ProjectViewController::class, 'index'])->name('projects.index');
        Route::get('/phases', [PhaseViewController::class, 'index'])->name('phases.index');
        Route::get('/stages', [StageViewController::class, 'index'])->name('stages.index');
        Route::get('/tickets', [TicketViewController::class, 'index'])->name('tickets.index');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('admin.dashboard.data');

        Route::get('/dashboard/boletos', [DashboardController::class, 'boletos'])->name('admin.dashboard.boletos');
        Route::resource('/users', UserController::class);


        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        */

        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');

        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
            ->name('roles.edit');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->name('roles.update');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->name('roles.destroy');
        Route::get('/sales', [SalesController::class, 'index'])->name('sales.index');


        /*
       |--------------------------------------------------------------------------
       | PERMISOS
       |--------------------------------------------------------------------------
       */
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->name('permissions.index');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->name('permissions.store');

        Route::delete(
            '/permissions/{permission}',
            [PermissionController::class, 'destroy']
        )->name('permissions.destroy');

    });

    /*
    |--------------------------------------------------------------------------
    | ORGANIZER (maneja eventos)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|organizer'])->group(function () {

        Route::get('/events/{event}/configurator', [EventosController::class, 'configurator'])->name('events.configurator');
    });

    /*
    |--------------------------------------------------------------------------
    | TAQUILLERO
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|taquillero'])->group(function () {

        Route::get('/taquilla', [EventosController::class, 'index'])->name('taquilla.index');
        Route::get('/taquilla/{event}/', [EventosController::class, 'iframe'])->name('eventPublic.index');
        Route::post('/taquilla/sell', [TaquillaController::class, 'sell']);
        Route::get('/taquilla/ticket/{instance}/pdf', [TaquillaController::class, 'pdf']);
        Route::get('/boletos/reprint', [PaymentController::class, 'reprint'])->name('boletos.reprint');
        Route::get('/ticket-instances', [TicketReprintController::class, 'index'])->name('admin.ticket_instances.index');

    });

    /*
    |--------------------------------------------------------------------------
    | SCANNER
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|scanner'])->group(function () {

        Route::get('/checkin', [CheckinController::class, 'index']);
        Route::post('/checkin/validate', [CheckinController::class, 'validateTicket']);
        Route::get('/checkin/stats', [CheckinController::class, 'stats'])->name('checkin.stats');

    });

    /*
    |--------------------------------------------------------------------------
    | FINANCE
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin|finance'])->group(function () {

        Route::get('/corte', [CorteController::class, 'index'])->name('admin.corte.index');
        Route::get('/corte/export/general', [CorteController::class, 'exportGeneral'])->name('admin.corte.export.general');

    });

    Route::middleware(['role:admin|cumbres|taquillero'])->group(function () {
        Route::get('/reimpresion', [RegistrationController::class, 'index'])->name('admin.registrations.index');
        Route::get('/registrations', [RegistrationController::class, 'index'])->name('admin.registrations.index');
        Route::get('/ticket-instances/{instance}/reprint', [TicketReprintController::class, 'reprintAdmin'])->name('admin.ticket_instances.reprint');
        Route::get('/registrations/{instance}/reprint', [TicketReprintController::class, 'reprintInscription'])->name('admin.registrations.reprint');
        Route::get('/admin/registrations/export', [RegistrationController::class, 'export'])->name('admin.registrations.export');
    });



    //perfil
    Route::get('/perfil', [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/perfil/actualizar', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.update.photo');
    Route::post('/perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

});

Route::get('/pago', [PaymentController::class, 'formulario'])->name('pago.form');
Route::post('/pago/intent', [PaymentController::class, 'crearIntent'])->name('pago.intent');
Route::get('/pago/success', [PaymentController::class, 'success'])->name('pago.success');
Route::get('/pago/cancel', [PaymentController::class, 'cancel'])->name('pago.cancel');

Route::get('/cart', [App\Http\Controllers\CartController::class, 'get'])->name('cart.get');
Route::post('/cart/add', [App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove', [App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');

Route::post('/tickets/resend', [TicketResendController::class, 'resend']);
Route::get('/event/{lot}/', [EventosController::class, 'iframe'])->name('eventPublic.index');
Route::get('/wallet/{instance}', [WalletTestController::class, 'testWallet'])->name('wallet.add');

// =========================
// Auth Routes
// =========================
require __DIR__ . '/auth.php';
