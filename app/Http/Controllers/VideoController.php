<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Video;
use App\Models\Formation;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Vérifier si l'utilisateur est connecté et s'il a le rôle admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Récupérer toutes les vidéos et ajouter l'URL complète
        $videos = Video::all()->map(function($video) {
            $video->video = Storage::disk('public')->url($video->video);
            return $video;
        });

        return response()->json($videos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        // Vérifier que l'utilisateur est bien connecté et a le rôle d'admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Valider les données de la requête
        $validated = $request->validated();

        try {
            // Stocker la vidéo dans public/storage/videos
            $path = $request->file('video')->store('videos', 'public');
            $validated['video'] = $path;

            // Générer l'URL complète de la vidéo
            $videoUrl = Storage::disk('public')->url($path);

            // Log de succès
            Log::info('Vidéo stockée avec succès dans public/storage/videos', ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement de la vidéo', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur de téléchargement', 'error' => $e->getMessage()], 500);
        }

        // Enregistrer les informations dans la base de données
        $video = Video::create($validated);

        // Retourner la réponse avec le lien public de la vidéo
        return response()->json([
            'message' => 'Vidéo ajoutée avec succès',
            'video_url' => $videoUrl
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video)
    {
        // Ajouter l'URL complète de la vidéo
        $video->video = Storage::disk('public')->url($video->video);
        return response()->json($video);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        // Vérifier si l'utilisateur est connecté et qu'il a le rôle admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        // Stocker le fichier si la vidéo est modifiée
        if ($request->hasFile('video')) {
            // Supprimer l'ancienne vidéo
            if ($video->video) {
                Storage::disk('public')->delete($video->video);
            }

            // Stocker la nouvelle vidéo
            $path = $request->file('video')->store('videos', 'public');
            $validated['video'] = $path;
        }

        // Mettre à jour la vidéo
        $video->update($validated);

        return response()->json([
            'message' => 'Vidéo mise à jour avec succès',
            'video_url' => isset($path) ? Storage::disk('public')->url($path) : null
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        // Vérifier si l'utilisateur est connecté et qu'il a le rôle admin
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        // Supprimer la vidéo du stockage public si elle existe
        if ($video->video) {
            Storage::disk('public')->delete($video->video);
        }

        // Supprimer l'enregistrement de la base de données
        $video->delete();

        return response()->json(['message' => 'Vidéo supprimée avec succès'], 200);
    }

    /**
     * Afficher les vidéos d'une formation avec ses ressources associées.
     */
    public function videoRessources($formationId)
    {
        // Récupérer la formation avec ses vidéos et les ressources associées
        $formation = Formation::with(['videos.ressources'])->find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }

        // Ajouter l'URL complète des vidéos
        $formation->videos->each(function($video) {
            $video->video = Storage::disk('public')->url($video->video);
        });

        return response()->json($formation);
    }
}
