<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Video;
use App\Models\Formation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Http\Requests\StoreVideoRequest;
use App\Http\Requests\UpdateVideoRequest;

class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $videos = Video::all();
        return response()->json($videos);
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
            Log::info('Début du traitement de la vidéo');

            // Vérifier si le fichier est présent dans la requête
            if (!$request->hasFile('video')) {
                Log::error('Aucun fichier vidéo n\'a été trouvé dans la requête');
                return response()->json(['message' => 'Erreur : aucun fichier vidéo n\'a été fourni'], 400);
            }

            $videoFile = $request->file('video');
            Log::info('Fichier vidéo reçu', [
                'original_name' => $videoFile->getClientOriginalName(),
                'size' => $videoFile->getSize(),
                'mime_type' => $videoFile->getMimeType()
            ]);

            // Stocker la vidéo dans S3 et obtenir le chemin
            $path = $videoFile->store('videos', 's3');

            if (!$path) {
                Log::error('Échec du stockage de la vidéo sur S3');
                return response()->json(['message' => 'Erreur : le fichier n\'a pas été correctement téléchargé sur S3'], 500);
            }

            Log::info('Vidéo stockée sur S3', ['s3_path' => $path]);

            // Générer l'URL publique depuis S3
            $publicUrl = Storage::disk('s3')->url($path);
            Log::info('URL publique générée', ['public_url' => $publicUrl]);

            // Enregistrer l'URL publique dans la base de données
            $validated['video'] = $publicUrl;

            $video = Video::create($validated);
            Log::info('Vidéo enregistrée dans la base de données', ['video_id' => $video->id]);

            return response()->json([
                'message' => 'Vidéo ajoutée avec succès',
                'video_url' => $video->video
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la vidéo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur de traitement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Video $video)
    {
        return response()->json($video);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVideoRequest $request, Video $video)
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $validated = $request->validated();

        try {
            if ($request->hasFile('video')) {
                Log::info('Mise à jour de la vidéo', ['video_id' => $video->id]);

                // Supprimer l'ancienne vidéo de S3
                $oldPath = parse_url($video->video, PHP_URL_PATH);
                Storage::disk('s3')->delete($oldPath);
                Log::info('Ancienne vidéo supprimée de S3', ['old_path' => $oldPath]);

                // Stocker la nouvelle vidéo sur S3
                $path = $request->file('video')->store('videos', 's3');

                if (!$path) {
                    Log::error('Échec du stockage de la nouvelle vidéo sur S3');
                    return response()->json(['message' => 'Erreur : le fichier n\'a pas été correctement téléchargé sur S3'], 500);
                }

                Log::info('Nouvelle vidéo stockée sur S3', ['s3_path' => $path]);

                // Générer la nouvelle URL publique
                $publicUrl = Storage::disk('s3')->url($path);
                Log::info('Nouvelle URL publique générée', ['public_url' => $publicUrl]);

                $validated['video'] = $publicUrl;
            }

            $video->update($validated);
            Log::info('Vidéo mise à jour dans la base de données', ['video_id' => $video->id]);

            return response()->json([
                'message' => 'Vidéo mise à jour avec succès',
                'video_url' => $video->video
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la vidéo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur de traitement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Video $video)
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        try {
            // Supprimer la vidéo de S3
            $path = parse_url($video->video, PHP_URL_PATH);
            Storage::disk('s3')->delete($path);
            Log::info('Vidéo supprimée de S3', ['s3_path' => $path]);

            $video->delete();
            Log::info('Vidéo supprimée de la base de données', ['video_id' => $video->id]);

            return response()->json(['message' => 'Vidéo supprimée avec succès'], 200);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la vidéo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Erreur de traitement', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Afficher les vidéos d'une formation avec ses ressources associées.
     */
    public function videoRessources($formationId)
    {
        $formation = Formation::with(['videos.ressources'])->find($formationId);

        if (!$formation) {
            return response()->json(['message' => 'Formation non trouvée'], 404);
        }

        return response()->json($formation);
    }

    /**
     * Stream la vidéo.
     */
    public function streamVideo($filename)
    {
        $path = storage_path('app/public/videos/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        $stream = new \App\Http\VideoStream($path);
        return response()->stream(function() use ($stream) {
            $stream->start();
        });
    }
}
