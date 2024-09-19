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

    // Récupérer toutes les vidéos si l'utilisateur est admin
    $videos = Video::all();
    return response()->json($videos);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVideoRequest $request)
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        try {
            // Log de la taille du fichier
            Log::info('Taille du fichier envoyé :', ['taille' => $request->file('video')->getSize()]);

            // Stocker sur Wasabi
            $path = $request->file('video')->store('videos', 'wasabi');
            $validated['video'] = $path;

            // Log de succès
            Log::info('Vidéo stockée avec succès sur Wasabi', ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement de la vidéo', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Erreur de téléchargement', 'error' => $e->getMessage()], 500);
        }

        Video::create($validated);

        return response()->json([
            'message' => 'Vidéo ajoutée avec succès',
            'video_path' => Storage::disk('wasabi')->url($path)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video)
    {
        return response()->json($video);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Video $video)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        //verifier si l'utilisateur est connecter et que qu'il a le rôle de admin
        if (!auth()->check() ||!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        //stocker le ficher si il est modifié
        if ($request->hasFile('video')) {
            if ($video->video) {
                Storage::disk('public')->delete($video->video);
            }
            $path = $request->file('video')->store('videos', 'public');
            $validated['video'] = $path;
        }

        //mettre a jour la video
        $video->update($validated);
        return response()->json(['message' => 'Vidéo mise à jour avec succès'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        //verifier si l'utilisateur est connecter et que qu'il a le rôle de admin
        if (!auth()->check() ||!auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        //supprimer la video
        if ($video->video) {
            Storage::disk('public')->delete($video->video);
        }
        $video->delete();
        return response()->json(['message' => 'Vidéo supprimée avec succès'], 200);
    }
    //afficher les vidéo d'une formation et ses ressources
    public function videoRessources($formationId)
    {
        // Récupérer la formation avec ses vidéos et les ressources associées aux vidéos
        $formation = Formation::with(['videos.ressources'])->find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }

        return response()->json($formation);
    }

}
