<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\LotController;
use App\Http\Controllers\API\ChatbotAdminController;
use App\Http\Controllers\View\TicketViewController;

// Lotes
Route::apiResource('tickets', LotController::class);

Route::post('/tickets/import', [TicketViewController::class, 'import']);

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
