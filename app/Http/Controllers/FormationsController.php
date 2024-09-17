<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormationsRequest;
use App\Http\Requests\UpdateFormationsRequest;
use App\Models\Formation;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;


class FormationsController extends Controller
{
    /**
     * Afficher la liste des formations.
     */
    public function index()
    {
        $formations = Formation::all();
        return response()->json($formations);
    }

    /**
     * Ajouter une nouvelle formation (réservé aux admins).
     */
    public function store(StoreFormationsRequest $request)
    {
        // Vérifier que l'utilisateur est connecté et possède le rôle 'admin'
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Créer une nouvelle formation
        $formation = Formation::create($request->validated());

        return response()->json(['message' => 'Formation créée avec succès', 'formation' => $formation], 201);
    }

    /**
     * Afficher une formation spécifique.
     */
    public function show(Formation $formation)
    {
        return response()->json($formation);
    }

    /**
     * Mettre à jour une formation (réservé aux admins).
     */
    public function update(UpdateFormationsRequest $request, Formation $formation)
    {
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Mettre à jour manuellement les champs
        $formation->nom_formation = $request->input('nom_formation');
        $formation->description = $request->input('description');

        // Sauvegarder explicitement
        $formation->save();

        return response()->json(['message' => 'Formation mise à jour avec succès', 'formation' => $formation]);
    }


    /**
     * Supprimer une formation (réservé aux admins).
     */
    public function destroy(Formation $formation)
    {
        // Vérifier que l'utilisateur est connecté et possède le rôle 'admin'
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $formation->delete();

        return response()->json(['message' => 'Formation supprimée avec succès']);
    }
}
