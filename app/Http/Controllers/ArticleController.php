<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;

class ArticleController extends Controller
{
    /**
     * Afficher tous les articles.
     */
    public function index()
    {
        return Article::all();
    }

    /**
     * Créer un nouvel article.
     */
    public function store(StoreArticleRequest $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validatedData = $request->validated();

        // Gestion de l'upload de l'image
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('articles_photos', 'public');
            $validatedData['photo'] = $path;
        }

        // Assigner l'utilisateur authentifié comme créateur de l'article
        $article = Article::create(array_merge($validatedData, ['user_id' => auth()->id()]));

        return response()->json($article, 201);
    }

    /**
     * Afficher un article spécifique.
     */
    public function show($id)
    {
        $article = Article::findOrFail($id);

        return response()->json($article);
    }

    /**
     * Mettre à jour un article existant.
     */
    public function update(UpdateArticleRequest $request, $id)
    {
      if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        $article = Article::findOrFail($id);

        $validatedData = $request->validated();

        // Mise à jour de l'image si une nouvelle est fournie
        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo
            if ($article->photo) {
                Storage::disk('public')->delete($article->photo);
            }

            // Enregistrer la nouvelle photo
            $path = $request->file('photo')->store('articles_photos', 'public');
            $validatedData['photo'] = $path;
        }

        $article->update($validatedData);

        return response()->json($article, 200);
    }

    /**
     * Supprimer un article.
     */
    public function destroy($id)
    {
      if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }
        $article = Article::findOrFail($id);


        // Supprimer la photo liée
        if ($article->photo) {
            Storage::disk('public')->delete($article->photo);
        }

        $article->delete();

        return response()->json(null, 204);
    }

    /**
     * Afficher tous les articles créés par un mentor spécifique.
     */
}
