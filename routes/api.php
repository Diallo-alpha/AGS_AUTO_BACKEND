<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\FormationsController;
use App\Http\Controllers\PhotoFormationController;



// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//Route public
Route::get('/formations', [FormationsController::class, 'index']);
Route::get('/formations/{formation}', [FormationsController::class, 'show']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

//Route pour connexion
Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::patch('update', [AuthController::class, 'update']);
    Route::delete('delete', [AuthController::class, 'delete']);

});

//Route pour admin
Route::middleware('auth:api', 'role:admin')->group(function () {
    Route::post('/formations', [FormationsController::class, 'store']);
    Route::put('/formations/{formation}', [FormationsController::class, 'update']);
    Route::delete('/formations/{formation}', [FormationsController::class, 'destroy']);
    //photos formations
    Route::get('/photo_formations', [PhotoFormationController::class, 'index']);
    Route::get('/photo_formations/{photoFormation}', [PhotoFormationController::class, 'show']);
    Route::post('/photo_formations', [PhotoFormationController::class, 'store']);
    Route::post('/photo_formations/{photoFormation}', [PhotoFormationController::class, 'update']);
    Route::delete('/photo_formations/{photoFormation}', [PhotoFormationController::class, 'destroy']);
    //ressources
    Route::get('/ressources', [RessourceController::class, 'index']);
    Route::get('/ressources/{ressource}', [RessourceController::class, 'show']);
    Route::post('/ressources', [RessourceController::class, 'store']);
    Route::post('/ressources/{ressource}', [RessourceController::class, 'update']);
    Route::delete('/ressources/{ressource}', [RessourceController::class, 'destroy']);
    //Route pour les vidéos
    Route::get('/videos', [VideoController::class, 'index']);
    Route::get('/videos/{video}', [VideoController::class, 'show']);
    Route::post('/videos', [VideoController::class, 'store']);
    Route::patch('/videos/{video}', [VideoController::class, 'update']);
    Route::delete('/videos/{video}', [VideoController::class, 'destroy']);
});
