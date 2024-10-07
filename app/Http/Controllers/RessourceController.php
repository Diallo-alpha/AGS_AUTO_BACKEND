<?php
namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Ressource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreRessourceRequest;
use App\Http\Requests\UpdateRessourceRequest;

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
        Log::info('Données reçues:', $request->all());

        try {
            $validated = $request->validated();
            Log::info('Données validées:', $validated);

            if (!isset($validated['titre']) || !isset($validated['video_id'])) {
                throw new \Exception('Les champs titre et video_id sont requis.');
            }

            $ressource = new Ressource();
            $ressource->titre = $validated['titre'];
            $ressource->video_id = $validated['video_id'];

            if ($request->hasFile('documents')) {
                $path = $request->file('documents')->store('ressources_videos', 'public');
                $ressource->documents = $path;
            } else {
                throw new \Exception('Le fichier documents est requis.');
            }

            $ressource->save();
            Log::info('Ressource sauvegardée:', $ressource->toArray());

            return response()->json([
               'message' => 'Ressource ajoutée avec succès',
               'ressource' => $ressource
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la sauvegarde:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Erreur lors de l\'ajout de la ressource',
                'error' => $e->getMessage()
            ], 500);
        }
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

    //afficher les ressource d'une vidéos
    public function getResourcesByVideoId($videoId)
    {
        try {
            // Vérifier si la vidéo existe
            $video = Video::findOrFail($videoId);

            // Récupérer toutes les ressources associées à cette vidéo
            $resources = Ressource::where('video_id', $videoId)->get();

            return response()->json([
                'message' => 'Ressources récupérées avec succès',
                'video' => $video->titre, // Ajout du titre de la vidéo pour plus de contexte
                'resources' => $resources
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des ressources:', [
                'video_id' => $videoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des ressources',
                'error' => $e->getMessage()
            ], 500);
        }
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
