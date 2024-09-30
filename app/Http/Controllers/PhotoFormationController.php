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
        $photoFormations = PhotoFormation::all()->map(function ($photo) {
            $photo->photo = $photo->photo ? asset('storage/' . $photo->photo) : null;
            return $photo;
        });
        return response()->json($photoFormations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePhotoFormationRequest $request)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('formations', 'public');
            $validated['photo'] = $path;
        }

        $photoFormation = PhotoFormation::create($validated);

        $photoFormation->photo = $photoFormation->photo ? asset('storage/' . $photoFormation->photo) : null;

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
        $photoFormation->photo = $photoFormation->photo ? asset('storage/' . $photoFormation->photo) : null;
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
            if ($photoFormation->photo) {
                Storage::disk('public')->delete($photoFormation->photo);
            }

            $path = $request->file('photo')->store('formations', 'public');
            $validated['photo'] = $path;
        }

        $photoFormation->update($validated);

        $photoFormation->photo = $photoFormation->photo ? asset('storage/' . $photoFormation->photo) : null;

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
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($photoFormation->photo) {
            Storage::disk('public')->delete($photoFormation->photo);
        }

        $photoFormation->delete();

        return response()->json(['message' => 'Photo supprimée avec succès']);
    }

    //afficher les photos d'une formation spécifique
    public function getPhotosByFormation($formationId)
    {
        $formation = Formation::findOrFail($formationId);

        $photos = $formation->photos->map(function ($photo) {
            $photo->photo = $photo->photo ? asset('storage/' . $photo->photo) : null;
            return $photo;
        });

        return response()->json($photos);
    }
}
