<?php
namespace App\Http\Controllers;

use App\Http\Requests\StoreRessourceRequest;
use App\Http\Requests\UpdateRessourceRequest;
use App\Models\Ressource;
use Illuminate\Support\Facades\Storage;

class RessourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Afficher les ressources que pour les admins
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $ressources = Ressource::all();
        return response()->json($ressources);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Pas utilisé dans une API, mais nécessaire pour un CRUD standard
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRessourceRequest $request)
    {
        // Ajouter une ressource, seulement pour les admins
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        if ($request->hasFile('documents')) {
            try {
                // Stocker le fichier et obtenir le chemin
                $path = $request->file('documents')->store('ressources_videos', 'public');
                $validated['documents'] = $path;
            } catch (\Exception $e) {
                return response()->json(['message' => 'Erreur lors du téléchargement du fichier', 'error' => $e->getMessage()], 500);
            }
        }

        // Créer une nouvelle ressource
        $ressource = Ressource::create($validated);

        \Log::info('Fichier reçu : ', ['file' => $request->file('fichier')]);

        return response()->json([
           'message' => 'Ressource ajoutée avec succès',
           'ressource' => $ressource
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ressource $ressource)
    {
        // Afficher une ressource
        return response()->json($ressource);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ressource $ressource)
    {
        // Pas utilisé dans une API, mais nécessaire pour un CRUD standard
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(UpdateRessourceRequest $request, Ressource $ressource)
    {
        // Vérifier si l'utilisateur est connecté et qu'il a le rôle d'admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Validation des données de la requête
        $validated = $request->validated();

        // Vérifier s'il y a un fichier sous le champ "documents" dans la requête
        if ($request->hasFile('documents')) {
            // Supprimer l'ancien fichier s'il existe
            if ($ressource->documents) {
                Storage::disk('public')->delete($ressource->documents);
            }

            // Stocker le nouveau fichier et mettre à jour l'attribut 'documents'
            $path = $request->file('documents')->store('ressources_videos', 'public');
            $validated['documents'] = $path;
        }

        // Mettre à jour les champs validés
        $ressource->update($validated);

        return response()->json([
            'message' => 'Ressource modifiée avec succès',
            'ressource' => $ressource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ressource $ressource)
    {
        // Supprimer, seulement pour les admins
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($ressource->fichier) {
            Storage::disk('public')->delete($ressource->fichier);
        }

        $ressource->delete();

        return response()->json(['message' => 'Ressource supprimée avec succès']);
    }
}
