<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreProduitRequest;
use App\Http\Requests\UpdateProduitRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    /**
     * Affiche une liste des produits avec pagination.
     * Retourne tous les produits en format JSON.
     */
    public function index(): JsonResponse
    {

        // Ajoute la pagination
        $produits = Produit::paginate(10);
        $produits->getCollection()->transform(function ($produit) {
            $produit->image = $produit->image ? asset('storage/' . $produit->image) : null;
            return $produit;
        });
        return response()->json($produits, 200);
    }

    /**
     * Stocke une nouvelle ressource (produit).
     * Valide et crée un nouveau produit.
     */
    public function store(StoreProduitRequest $request): JsonResponse
    {
        // Vérifie le rôle de l'utilisateur connecté
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Validation des données reçues
        $validated = $request->validated();

        // Stocke le fichier d'image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('produits', 'public');
            $validated['image'] = $path;
        }

        // Crée le produit
        $produit = Produit::create($validated);

        // Ajoutez l'URL complète de l'image
        $produit->image = $produit->image ? asset('storage/' . $produit->image) : null;

        return response()->json($produit, 201); // Retourne le produit créé
    }

    /**
     * Affiche un produit spécifique par son ID.
     */
    public function show(string $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        return response()->json($produit, 200);
    }

    /**
     * Met à jour une ressource (produit) par son ID.
     * Met à jour un produit existant.
     */
    public function update(UpdateProduitRequest $request, string $id): JsonResponse
    {
        // Vérifie si l'utilisateur est authentifié et possède le rôle 'admin'
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Rechercher le produit
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        // Valider les données de la requête
        $validated = $request->validated();

        // Gérer le fichier image
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // Supprimer l'ancienne image s'il en existe une
            if ($produit->image) {
                Storage::disk('public')->delete($produit->image);
            }

            // Stocker la nouvelle image
            $filePath = $file->store('produits', 'public');
            $validated['image'] = $filePath;
        }

        // Mettre à jour le produit
        $produit->update($validated);

        $produit->image = $produit->image ? asset('storage/' . $produit->image) : null;

        return response()->json($produit, 200);
    }

    /**
     * Supprime une ressource (produit) par son ID.
     * Supprime un produit et son image associée, le cas échéant.
     */
    public function destroy(string $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404);
        }

        // Supprimer l'image associée
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        // Supprimer le produit
        $produit->delete();

        return response()->json(['message' => 'Produit supprimé avec succès'], 200);
    }

    //recuperer les d'une categoris

       /**
     * Récupère les produits d'une catégorie spécifique.
     *
     * @param int $categoriId L'ID de la catégorie
     * @return JsonResponse
     */
    public function getProductsByCategory(int $categoriId): JsonResponse
    {
        $produits = Produit::where('categorie_id', $categoriId)->paginate(10);

        if ($produits->isEmpty()) {
            return response()->json(['message' => 'Aucun produit trouvé pour cette catégorie'], 404);
        }

        $produits->getCollection()->transform(function ($produit) {
            $produit->image = $produit->image ? asset('storage/' . $produit->image) : null;
            return $produit;
        });

        return response()->json($produits, 200);
    }
}
