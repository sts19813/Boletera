<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PhaseController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\LotController;

// Proyectos
Route::apiResource('projects', ProjectController::class);

// Fases
Route::apiResource('phases', PhaseController::class);

// Etapas
Route::apiResource('stages', StageController::class);

// Lotes
Route::apiResource('tickets', LotController::class);


Route::post('/tickets/import', [LotController::class, 'import']);
Route::put('/tickets/{ticket}/status', [LotController::class, 'updateStatus']);
Route::post('/tickets/{ticket}/chepina', [LotController::class, 'uploadChepina']);
