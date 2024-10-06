<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreFormationsRequest;
use App\Http\Requests\UpdateFormationsRequest;
use Exception;

class FormationsController extends Controller
{
    public function index()
    {
        try {
            $formations = Formation::all();
            foreach ($formations as $formation) {
                $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;
            }
            return response()->json($formations);
        } catch (Exception $e) {
            Log::error('Erreur lors de la récupération des formations: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la récupération des formations'], 500);
        }
    }

    public function store(StoreFormationsRequest $request)
    {
        Log::info('Début de la méthode store');
        Log::info('Données reçues pour la création de formation:', $request->all());

        try {
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                Log::warning('Tentative d\'accès non autorisé à la création de formation');
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            $validatedData = $request->validated();
            Log::info('Données validées:', $validatedData);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('formations_image', 'public');
                $validatedData['image'] = $path;
                Log::info('Image stockée: ' . $path);
            }

            $formation = Formation::create($validatedData);
            Log::info('Formation créée avec succès. ID: ' . $formation->id);

            $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;

            return response()->json(['message' => 'Formation créée avec succès', 'formation' => $formation], 201);
        } catch (Exception $e) {
            Log::error('Erreur lors de la création de la formation: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur lors de la création de la formation', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Formation $formation)
    {
        try {
            $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;
            return response()->json($formation);
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'affichage de la formation: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'affichage de la formation'], 500);
        }
    }

    public function update(UpdateFormationsRequest $request, Formation $formation)
    {
        try {
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                Log::warning('Tentative d\'accès non autorisé à la mise à jour de formation');
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            $validatedData = $request->validated();
            Log::info('Données validées pour la mise à jour:', $validatedData);

            if ($request->hasFile('image')) {
                if ($formation->image) {
                    Storage::disk('public')->delete($formation->image);
                    Log::info('Ancienne image supprimée: ' . $formation->image);
                }

                $path = $request->file('image')->store('formations_image', 'public');
                $validatedData['image'] = $path;
                Log::info('Nouvelle image stockée: ' . $path);
            }

            $formation->update($validatedData);
            Log::info('Formation mise à jour avec succès. ID: ' . $formation->id);

            $formation->image = $formation->image ? asset('storage/' . $formation->image) : null;

            return response()->json(['message' => 'Formation mise à jour avec succès', 'formation' => $formation]);
        } catch (Exception $e) {
            Log::error('Erreur lors de la mise à jour de la formation: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur lors de la mise à jour de la formation', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Formation $formation)
    {
        try {
            if (!Auth::check() || !Auth::user()->hasRole('admin')) {
                Log::warning('Tentative d\'accès non autorisé à la suppression de formation');
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            if ($formation->image) {
                Storage::disk('public')->delete($formation->image);
                Log::info('Image supprimée: ' . $formation->image);
            }

            $formation->delete();
            Log::info('Formation supprimée avec succès. ID: ' . $formation->id);

            return response()->json(['message' => 'Formation supprimée avec succès']);
        } catch (Exception $e) {
            Log::error('Erreur lors de la suppression de la formation: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json(['message' => 'Erreur lors de la suppression de la formation', 'error' => $e->getMessage()], 500);
        }
    }
}
