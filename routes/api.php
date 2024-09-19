<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\CategorieController;
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
    Route::post('/video/ajouter', [VideoController::class, 'store']);
    Route::get('/videos/{video}', [VideoController::class, 'show']);
    Route::post('/videos/{video}', [VideoController::class, 'update']);
    Route::get('/formations/{formation}/videos', [VideoController::class, 'videoRessources']);
    Route::delete('/videos/{video}', [VideoController::class, 'destroy']);
    //les ressouces
    Route::apiResource('categories', CategorieController::class);
    //Produits
    // Route::apiResource('produits', ProduitController::class);
    Route::get('/produits', [ProduitController::class, 'index']);
    Route::get('/produits/{produit}', [ProduitController::class,'show']);
    Route::post('/produits', [ProduitController::class,'store']);
    Route::post('/produits/{produit}', [ProduitController::class,'update']);
    Route::delete('/produits/{produit}', [ProduitController::class,'destroy']);

});
