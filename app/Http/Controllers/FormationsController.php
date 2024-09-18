<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhotoFormationRequest;
use App\Http\Requests\UpdatePhotoFormationRequest;
use App\Models\PhotoFormation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PhotoFormationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $photoFormations = PhotoFormation::all();
        return response()->json($photoFormations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePhotoFormationRequest $request)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            // Stocker le fichier et obtenir le chemin
            $path = $request->file('photo')->store('formations', 'public');
            $validated['photo'] = $path;
        }

        // Créer une nouvelle photo pour la formation
        $photoFormation = PhotoFormation::create($validated);

        return response()->json([
            'message' => 'Photo ajoutée avec succès',
            'photoFormation' => $photoFormation
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PhotoFormation $photoFormation)
    {
        return response()->json($photoFormation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePhotoFormationRequest $request, PhotoFormation $photoFormation)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Valider les données
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo si elle existe
            if ($photoFormation->photo) {
                Storage::disk('public')->delete($photoFormation->photo);
            }

            // Stocker la nouvelle photo
            $path = $request->file('photo')->store('formations', 'public');
            $validated['photo'] = $path;
        }

        // Mettre à jour les informations de la photo (manuellement, champ par champ)
        $photoFormation->titre = $validated['titre'] ?? $photoFormation->titre;
        $photoFormation->description = $validated['description'] ?? $photoFormation->description;
        $photoFormation->photo = $validated['photo'] ?? $photoFormation->photo;

        // Sauvegarder explicitement les changements
        $photoFormation->save();

        return response()->json([
            'message' => 'Photo mise à jour avec succès',
            'photoFormation' => $photoFormation
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PhotoFormation $photoFormation)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Supprimer le fichier de la photo
        if ($photoFormation->photo) {
            Storage::disk('public')->delete($photoFormation->photo);
        }

        $photoFormation->delete();

        return response()->json(['message' => 'Photo supprimée avec succès']);
    }
}
