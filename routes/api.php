<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PolygonController; // Mano prideta
use App\Http\Controllers\PaselioController;
use App\Models\Polygon;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/check-auth', function () {
    return response()->json(['authenticated' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('/polygons/{id}', [PolygonController::class, 'update']);
    Route::post('/polygons', [PolygonController::class, 'store']);
    Route::get('/polygons', [PolygonController::class, 'index']);
    Route::post('/polygons/{polygon}/paseliai', [PaselioController::class, 'update']);
    
    Route::get('/paseliai', [PaselioController::class, 'index']);
    Route::post('/paseliai', [PaselioController::class, 'store']);
});

Route::get('/check-auth', function () {
    return response()->json(['authenticated' => auth()->check()]);
})->middleware('auth:sanctum');
