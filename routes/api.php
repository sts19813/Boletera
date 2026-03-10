<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\PhaseController;
use App\Http\Controllers\API\StageController;
use App\Http\Controllers\API\LotController;
use App\Http\Controllers\API\ChatbotAdminController;
use App\Http\Controllers\View\TicketViewController;
// Proyectos
Route::apiResource('projects', ProjectController::class);

// Fases
Route::apiResource('phases', PhaseController::class);

// Etapas
Route::apiResource('stages', StageController::class);

// Lotes
Route::apiResource('tickets', LotController::class);

Route::post('/tickets/import', [TicketViewController::class, 'import']);
Route::put('/tickets/{ticket}/status', [LotController::class, 'updateStatus']);
Route::post('/tickets/{ticket}/chepina', [LotController::class, 'uploadChepina']);

Route::prefix('chatbot/admin')
    ->middleware('chatbot.admin')
    ->group(function () {
        Route::get('/events', [ChatbotAdminController::class, 'events']);
        Route::get('/events/upcoming', [ChatbotAdminController::class, 'upcomingEvents']);
        Route::get('/events/{evento}', [ChatbotAdminController::class, 'showEvent']);
        Route::get('/sales/overview', [ChatbotAdminController::class, 'salesOverview']);
        Route::get('/sales/search', [ChatbotAdminController::class, 'searchSales']);
        Route::get('/sales/latest', [ChatbotAdminController::class, 'latestSales']);
        Route::get('/availability', [ChatbotAdminController::class, 'availability']);
        Route::post('/taquilla/sell-cash', [ChatbotAdminController::class, 'sellCash']);
    });
