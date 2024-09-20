<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentaireRequest;
use App\Http\Requests\UpdateCommentaireRequest;
use App\Models\Commentaire;
use App\Models\Article;

class CommentaireController extends Controller
{
    /**
     * Afficher tous les commentaires.
     */
    public function index()
    {
        $commentaires = Commentaire::all();
        return response()->json($commentaires);
    }

    /**
     * Créer un nouveau commentaire.
     */
    public function store(StoreCommentaireRequest $request)
    {
        // Valider et créer un commentaire
        $validatedData = $request->validated();

        // Vérifier que l'article existe (grâce à la règle de validation)
        $article = Article::findOrFail($validatedData['article_id']);

        // Créer le commentaire
        $commentaire = Commentaire::create($validatedData);

        return response()->json($commentaire, 201);
    }

    /**
     * Afficher un commentaire spécifique.
     */
    public function show($id)
    {
        $commentaire = Commentaire::findOrFail($id);

        return response()->json($commentaire);
    }

    /**
     * Mettre à jour un commentaire existant.
     */
    public function update(UpdateCommentaireRequest $request, $id)
    {
        // Trouver le commentaire à mettre à jour
        $commentaire = Commentaire::findOrFail($id);

        // Valider et récupérer les données
        $validatedData = $request->validated();

        // Si aucune donnée n'est passée, renvoyer une erreur
        if (empty($validatedData)) {
            return response()->json(['message' => 'Aucune donnée à mettre à jour.'], 400);
        }

        // Mettre à jour le commentaire avec les données validées
        $commentaire->update($validatedData);

        return response()->json($commentaire, 200);
    }

    /**
     * Supprimer un commentaire.
     */
    public function destroy($id)
    {
        $commentaire = Commentaire::findOrFail($id);

        // Supprimer le commentaire
        $commentaire->delete();

        return response()->json(null, 204);
    }

    /**
     * Afficher les commentaires pour un article spécifique.
     */
    public function commentairesParArticle($article_id)
    {
        // Valider que l'article existe
        $article = Article::findOrFail($article_id);

        // Récupérer les commentaires associés
        $commentaires = Commentaire::where('article_id', $article_id)->get();

        return response()->json($commentaires);
    }
}

