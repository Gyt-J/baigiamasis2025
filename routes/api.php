<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PolygonController; // Mano prideta
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::put('/polygons/{id}', [PolygonController::class, 'update']);
Route::post('/polygons', [PolygonController::class, 'store']);
Route::get('/polygons', [PolygonController::class, 'index']); // Mano prideta, up2 irgi
