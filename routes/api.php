<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\FormationsController;
use App\Http\Controllers\NoteFormationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PartenaireController;
use App\Http\Controllers\PaytechController;
use App\Http\Controllers\PhotoFormationController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\ProgressionController;
use App\Http\Controllers\RessourceController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserFormationController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;


// Routes publiques
Route::group([], function () {
    // Partenaires
    Route::get('/partenaires', [PartenaireController::class, 'index']);
    Route::get('/partenaires/{partenaire}', [PartenaireController::class, 'show']);

    // Formations
    Route::get('/formations', [FormationsController::class, 'index']);
    Route::get('/formations/{formation}', [FormationsController::class, 'show']);

    // Authentification
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Services
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{service}', [ServiceController::class, 'show']);

    // Categories
    Route::get('categories', [CategorieController::class, 'index']);
    Route::get('categories/{id}', [CategorieController::class, 'show']);



    // Commentaires
    Route::get('/commentaires', [CommentaireController::class, 'index']);
    Route::get('/commentaires/{commentaire}', [CommentaireController::class, 'show']);
    Route::post('/commentaires', [CommentaireController::class, 'store']);

    // Articles
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);

    // Paiement Paytech
    Route::post('/payment/initiate', [PaytechController::class, 'initiatePayment'])->name('payment.initiate');
    Route::post('/paytech/notification', [PaytechController::class, 'handleNotification'])->name('paytech.notification');
    Route::post('/paytech/successful-payment', [PaytechController::class, 'handleSuccessfulPayment'])->name('paytech.successful-payment');    Route::get('/paytech/cancel', [PaytechController::class, 'paymentCancel'])->name('paytech.cancel');
    Route::get('/verify-payment', [PaytechController::class, 'verifyPayment'])->name('payment.verify');

    //afficher les produits
    Route::get('produit/categorie/{id}', [ProduitController::class, 'getProductsByCategory'])->name('produit.categorie');
});

// Routes authentifiées
Route::middleware('auth:api')->group(function () {
    // Authentification
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::patch('update', [AuthController::class, 'update']);
    Route::delete('delete', [AuthController::class, 'delete']);

    // Commandes
    Route::post('/commandes', [CommandeController::class, 'store']);
    Route::get('/commandes/{id}', [CommandeController::class, 'show']);
    Route::put('/commandes/{id}', [CommandeController::class, 'update']);
    Route::delete('/commandes/{id}', [CommandeController::class, 'destroy']);

    // Panier
    Route::get('/panier', [CartController::class, 'obtenirPanier'])->name('panier.obtenir');
    Route::post('/panier/ajouter', [CartController::class, 'ajouterAuPanier'])->name('panier.ajouter');
    Route::delete('/panier/retirer', [CartController::class, 'retirerDuPanier'])->name('panier.retirer');
    Route::put('/panier/mettre-a-jour', [CartController::class, 'mettreAJourQuantite'])->name('panier.mettreAJour');
});

// Routes administrateur
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    // Formations
    Route::post('/formations', [FormationsController::class, 'store']);
    Route::post('/formations/{formation}', [FormationsController::class, 'update']);
    Route::delete('/formations/{formation}', [FormationsController::class, 'destroy']);

    // Photos de formations
    Route::apiResource('photo_formations', PhotoFormationController::class);
    Route::get('formations/{formationId}/photos', [PhotoFormationController::class, 'getPhotosByFormation']);

    // Ressources
    Route::apiResource('ressources', RessourceController::class);

    // Vidéos
    Route::apiResource('videos', VideoController::class);
    Route::get('video/{filename}', [VideoController::class, 'streamVideo'])->name('stream.video');

    // Catégories
    Route::post('categories', [CategorieController::class, 'store']);
    Route::put('categories/{id}', [CategorieController::class, 'update']);
    Route::delete('categories/{id}', [CategorieController::class, 'destroy']);

    // Produits

    Route::get('produits',[ProduitController::class, 'index']);
    Route::post('produits', [ProduitController::class, 'store']);
    Route::get('produits/{id}', [ProduitController::class, 'show']);
    Route::post('produits/{id}', [ProduitController::class, 'update']);
    Route::get('delete{id}', [ProduitController::class, 'destroy']);

    // Articles
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);

    // Commentaires
    Route::patch('/commentaires/{commentaire}', [CommentaireController::class, 'update']);
    Route::delete('/commentaires/{commentaire}', [CommentaireController::class, 'destroy']);

    // Partenaires
    Route::post('/partenaires', [PartenaireController::class, 'store']);
    Route::post('/partenaires/{partenaire}', [PartenaireController::class, 'update']);
    Route::delete('/partenaires/{partenaire}', [PartenaireController::class, 'destroy']);

    // Services
    Route::post('/services', [ServiceController::class, 'store']);
    Route::post('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

    // Commandes
    Route::get('/commandes', [CommandeController::class, 'index']);
});

// Routes étudiant
Route::middleware(['auth:api', 'role:etudiant'])->group(function() {
    //afficher les vidéos d'une formation
    Route::get('/formations/{formation}/videos', [VideoController::class, 'videoRessources']);
    Route::get('video/{filename}', [VideoController::class, 'streamVideo'])->name('stream.video');


    //afficher formation d'un utilsateur
    Route::get('formation/acheter', [UserFormationController::class, 'index']);
    // Progressions
    Route::get('/progressions/{formationId}', [ProgressionController::class, 'show'])->name('progressions.show');
    Route::post('/progressions', [ProgressionController::class, 'store'])->name('progressions.store');
    Route::put('/progressions/{id}', [ProgressionController::class, 'update'])->name('progressions.update');

    // Notes
    Route::post('/notes', [NoteFormationController::class, 'store'])->name('notes.store');
    Route::put('/notes/{noteFormation}', [NoteFormationController::class, 'update'])->name('notes.update');

    // Paiements
    Route::get('/paiements', [PaytechController::class, 'index'])->name('paiements.index');
    //
    // Route::apiResource('videos', VideoController::class);
    Route::apiResource('ressources', RessourceController::class);
    //
    Route::get('videos/{videoId}/resources', [RessourceController::class, 'getResourcesByVideoId']);


});
