<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhotoFormationRequest;
use App\Http\Requests\UpdatePhotoFormationRequest;
use App\Models\Formation;
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
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            \Log::info('Fichier reçu:', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

            // Supprimer l'ancienne photo si elle existe
            if ($photoFormation->photo) {
                Storage::disk('public')->delete($photoFormation->photo);
            }

            // Stocker le nouveau fichier et mettre à jour l'attribut 'photo'
            $path = $file->store('formations', 'public');
            $validated['photo'] = $path;
        }

        // Mettre à jour les informations de la photo
        $photoFormation->update($validated);

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

    //afficher les photos d'une formation spécifique
    public function getPhotosByFormation($formationId)
    {
        // Trouver la formation par ID
        $formation = Formation::findOrFail($formationId);

        // Récupérer les photos associées à la formation
        $photos = $formation->photos->map(function ($photo) {
            // Ajouter l'URL complète de la photo
            if ($photo->photo) {
                $photo->photo_url = Storage::url($photo->photo);
            }
            return $photo;
        });

        return response()->json($photos);
    }
}
