<?php

namespace App\Http\Controllers;

use App\Models\Partenaire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StorePartenaireRequest;
use App\Http\Requests\UpdatePartenaireRequest;


class PartenaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $partenaires = Partenaire::all();

        foreach ($partenaires as $partenaire) {
            // Assigner l'URL complète du logo
            $partenaire->logo = $partenaire->logo ? asset('storage/' . $partenaire->logo) : null;
        }

        return response()->json($partenaires);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartenaireRequest $request)
{
    // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
    if (!Auth::check() || !Auth::user()->hasRole('admin')) {
        return response()->json(['message' => 'Accès refusé'], 403);
    }

    // Vérifier si le partenaire existe déjà
    $partenaire = Partenaire::where('nom_partenaire', $request->nom_partenaire)->first();
    if ($partenaire) {
        return response()->json(['message' => 'Le partenaire existe déjà'], 409);
    }

    // Valider les données de la requête
    $validated = $request->validated();

    if($request->hasfile('logo'))
    {
        // Stocker l'image du logo dans le storage
        $path = $request->file('logo')->store('logo-partenaire', 'public');
        $validated['logo'] = $path;
    }

    // Créer le nouveau partenaire
    $partenaire = Partenaire::create($validated);

    // Ajouter l'URL complète du logo avant de renvoyer la réponse
    $partenaire->logo = $partenaire->logo ? asset('storage/' . $partenaire->logo) : null;

    return response()->json([
        'message' => 'Partenaire créé avec succès',
        'partenaire' => $partenaire
    ], 201);
}


    /**
     * Display the specified resource.
     */
    public function show(Partenaire $partenaire)
    {
        // Afficher un partenaire
        return response()->json($partenaire);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartenaireRequest $request, Partenaire $partenaire)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasfile('logo')) {
            $file = $request->file('logo');
            \Log::info('Fichier reçu:', ['name' => $file->getClientOriginalName(), 'size' => $file->getSize()]);

            // Supprimer l'ancien logo s'il en existe un
            if ($partenaire->logo) {
                Storage::disk('public')->delete($partenaire->logo);
            }

            // Stocker le nouveau logo et mettre à jour l'attribut 'logo'
            $path = $file->store('logo-partenaire', 'public');
            $validated['logo'] = $path;
        }

        // Mettre à jour les informations du partenaire
        $partenaire->update($validated);

        // Ajouter l'URL complète du logo avant de renvoyer la réponse
        $partenaire->logo = $partenaire->logo ? asset('storage/' . $partenaire->logo) : null;

        return response()->json([
            'message' => 'Partenaire mis à jour avec succès',
            'partenaire' => $partenaire
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partenaire $partenaire)
    {
        // Vérifier que l'utilisateur est bien connecté et qu'il a le rôle d'admin
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Supprimer le logo s'il en existe un
        if ($partenaire->logo) {
            Storage::disk('public')->delete($partenaire->logo);
        }

        // Supprimer le partenaire
        $partenaire->delete();

        return response()->json(['message' => 'Partenaire supprimé avec succès']);
    }

}
