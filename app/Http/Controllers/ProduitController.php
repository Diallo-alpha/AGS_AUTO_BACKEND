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
     * Display a listing of the resource.
     * Return all products in JSON format.
     */
    public function index(): JsonResponse
    {
        $produits = Produit::all();
        return response()->json($produits, 200);
    }

    /**
     * Store a newly created resource in storage.
     * Validate and create a new product.
     */
    public function store(StoreProduitRequest $request): JsonResponse
    {
        //verifier le role de l'utilisateur connecter
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Validation des données reçues
        $validated = $request->validated();
        //stcoker le fichier
        if ($request->hasFile('image')){
            $path = $request->file('image')->store('produit', 'public');
            $validated['image'] = $path;
        }
        //crééer le produit
        $produit = Produit::create($validated);

        return response()->json($produit, 201); // Retourne le produit créé
    }

    /**
     * Display the specified resource.
     * Show a specific product by ID.
     */
    public function show(string $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404); // Produit non trouvé
        }

        return response()->json($produit, 200); // Retourne le produit
    }

    /**
     * Update the specified resource in storage.
     * Update a product by ID.
     */
    public function update(UpdateProduitRequest $request, string $id): JsonResponse
{
    // Vérifier si l'utilisateur est authentifié et possède le rôle 'admin'
    if (!Auth::check() || !Auth::user()->hasRole('admin')) {
        return response()->json(['message' => 'Accès refusé'], 403);
    }

    // Rechercher le produit
    $produit = Produit::find($id);

    if (!$produit) {
        return response()->json(['message' => 'Produit non trouvé'], 404); // Produit non trouvé
    }

    // Valider les données de la requête
    $validated = $request->validated();

    // Si un fichier image est envoyé, le traiter
    if ($request->hasFile('image')) {
        $file = $request->file('image');

        // Log du fichier reçu
        \Log::info('Fichier reçu:', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

        // Supprimer l'ancienne image s'il en existe une
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        // Stocker le fichier et obtenir le chemin
        $filePath = $file->store('produits', 'public');
        $validated['image'] = $filePath; // Ajouter l'image au tableau des données validées
    }

    // Mettre à jour le produit avec les champs validés
    $produit->update($validated);

    return response()->json($produit, 200); // Retourne le produit mis à jour
}



    /**
     * Remove the specified resource from storage.
     * Delete a product by ID.
     */
    public function destroy(string $id): JsonResponse
    {
        $produit = Produit::find($id);

        if (!$produit) {
            return response()->json(['message' => 'Produit non trouvé'], 404); // Produit non trouvé
        }

        $produit->delete();

        return response()->json(['message' => 'Produit supprimé avec succès'], 200); // Confirmation suppression
    }
}
