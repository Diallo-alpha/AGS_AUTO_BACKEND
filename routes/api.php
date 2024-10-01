<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PaytechController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\FormationsController;
use App\Http\Controllers\PartenaireController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\NoteFormationController;
use App\Http\Controllers\PhotoFormationController;



// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//Route public
//partenaires
Route::get('/partenaires', [PartenaireController::class, 'index']);
Route::get('/partenaires/{partenaire}', [PartenaireController::class, 'show']);
//
Route::get('/formations', [FormationsController::class, 'index']);
Route::get('/formations/{formation}', [FormationsController::class, 'show']);
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
//services
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/{service}', [ServiceController::class, 'show']);
//vidéos
Route::get('/formations/{formation}/videos', [VideoController::class, 'videoRessources']);
//route pour les commentaires
Route::get('/commentaires', [CommentaireController::class, 'index']);
Route::get('/commentaires/{commentaire}', [CommentaireController::class, 'show']);
Route::post('/commentaires', [CommentaireController::class, 'store']);
//route pour les articles
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{article}', [ArticleController::class, 'show']);
//route paiement
Route::post('/paytech-ipn', [PaytechController::class, 'handleIPN'])->name('paytech.ipn');
Route::get('/paiements/cancel/{id}', [PaytechController::class, 'paymentCancel'])->name('payment.cancel');
//callback
// route::post('/paiement/callback', [PaytechController::class, 'handleCallback'])->name('paiement.callback');
//Route pour connexion
Route::post('/payment/initiate', [PaytechController::class, 'initiatePayment'])->name('payment.initiate');
Route::middleware('auth:api')->group(function () {


    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::patch('update', [AuthController::class, 'update']);
    Route::delete('delete', [AuthController::class, 'delete']);
    //route pour les commandes
    Route::post('/commandes', [CommandeController::class, 'store']);
    Route::get('/commandes/{id}', [CommandeController::class, 'show']);
    Route::put('/commandes/{id}', [CommandeController::class, 'update']);
    Route::delete('/commandes/{id}', [CommandeController::class, 'destroy']);

    //paiement

    // Route::get('/paiements/success', [PaytechController::class, 'getSuccessfulPayments'])->name('paiements.success');
    // Route::post('/paiements/effectuer', [PaytechController::class, 'effectuerPaiement'])->name('paiements.effectuer');
    // route::get('/paiements/cancel', [PaytechController::class, 'paymentCancel'])->name('payment.cancel');
    // Route::post('/paiements/inscription/{formationId}', [PaytechController::class, 'inscrire'])->name('paiements.inscription');
    //pannier
     // Route pour obtenir le panier de l'utilisateur
     Route::get('/panier', [CartController::class, 'obtenirPanier'])->name('panier.obtenir');
     // Route pour ajouter un produit ou une formation au panier
     Route::post('/panier/ajouter', [CartController::class, 'ajouterAuPanier'])->name('panier.ajouter');

    // Route pour retirer un produit ou une formation du panier
      Route::delete('/panier/retirer', [CartController::class, 'retirerDuPanier'])->name('panier.retirer');

    // Route pour mettre à jour la quantité d'un produit ou d'une formation dans le panier
       Route::put('/panier/mettre-a-jour', [CartController::class, 'mettreAJourQuantite'])->name('panier.mettreAJour');

});

//Route pour admin
Route::middleware('auth:api', 'role:admin')->group(function () {
    //route formation admin
    Route::post('/formations', [FormationsController::class, 'store']);
    Route::post('/formations/{formation}', [FormationsController::class, 'update']);
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
    Route::delete('/videos/{video}', [VideoController::class, 'destroy']);
    Route::get('video/{filename}', [VideoController::class, 'streamVideo'])->name('stream.video');
    //les ressouces
    Route::apiResource('categories', CategorieController::class);
    //Produits
    Route::get('/produits', [ProduitController::class, 'index']);
    Route::get('/produits/{produit}', [ProduitController::class,'show']);
    Route::post('/produits', [ProduitController::class,'store']);
    Route::post('/produits/{produit}', [ProduitController::class,'update']);
    Route::delete('/produits/{produit}', [ProduitController::class,'destroy']);
    //route pour article
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);
    //cmmentaire pour admin
    Route::patch('/commentaires/{commentaire}', [CommentaireController::class, 'update']);
    Route::delete('/commentaires/{commentaire}', [CommentaireController::class, 'destroy']);
    //route pour les partenaire
    Route::post('/partenaires', [PartenaireController::class, 'store']);
    Route::post('/partenaires/{partenaire}', [PartenaireController::class, 'update']);
    Route::delete('/partenaires/{partenaire}', [PartenaireController::class, 'destroy']);
    //route por les services
    Route::post('/services', [ServiceController::class, 'store']);
    Route::post('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    //commandes
    Route::get('/commandes', [CommandeController::class, 'index']);
      //afficher les photos d'une formations
      Route::get('formations/{formationId}/photos', [PhotoFormationController::class, 'getPhotosByFormation']);

});

Route::middleware(['auth', 'role:etudiant'])->group(function() {
    Route::get('/progressions/{formationId}', [ProgressionController::class, 'show'])->name('progressions.show');
    Route::post('/progressions', [ProgressionController::class, 'store'])->name('progressions.store');
    Route::put('/progressions/{id}', [ProgressionController::class, 'update'])->name('progressions.update');
    Route::post('/notes', [NoteFormationController::class, 'store'])->name('notes.store');
    // Route pour mettre à jour une note existante
    Route::put('/notes/{noteFormation}', [NoteFormationController::class, 'update'])->name('notes.update');
    //paiemnts
    Route::get('/paiements', [PaytechController::class, 'index'])->name('paiements.index');
});
