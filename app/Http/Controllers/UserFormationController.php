<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Request;
use App\Models\UserFormation;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreUserFormationRequest;
use App\Http\Requests\UpdateUserFormationRequest;

class UserFormationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserFormationRequest $request)
    {
        // Stocker les informations dans la table utilisateur_formation
        $userFormation = UserFormation::create([
            'user_id' => $request->user_id,
            'formation_id' => $request->formation_id,
            'date_achat' => now(),
        ]);

        return response()->json(['message' => 'Formation achetée avec succès!', 'data' => $userFormation], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserFormation $userFormation)
    {
        return response()->json($userFormation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserFormation $userFormation)
    {
        // Si vous avez besoin d'une logique d'édition, vous pouvez la gérer ici.
        return response()->json($userFormation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserFormationRequest $request, UserFormation $userFormation)
    {
        // Mettre à jour les informations de la formation achetée
        $userFormation->update($request->validated());

        return response()->json(['message' => 'Formation mise à jour avec succès!', 'data' => $userFormation]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserFormation $userFormation)
    {
        $userFormation->delete();

        return response()->json(['message' => 'Formation supprimée avec succès!']);
    }

    /**
     * Affiche toutes les formations d'un utilisateur.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        Log::info('Début de la méthode index');

        // Récupère toutes les formations achetées avec leurs progressions
        $formations = Formation::select('formations.*')
            ->join('user_formations', 'formations.id', '=', 'user_formations.formation_id')
            ->leftJoin('progressions', function($join) use ($user) {
                $join->on('formations.id', '=', 'progressions.formation_id')
                     ->where('progressions.user_id', '=', $user->id);
            })
            ->where('user_formations.user_id', $user->id)
            ->with(['videos'])
            ->get()
            ->map(function ($formation) use ($user) {
                $progression = $formation->progressions()
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $formation->id,
                    'titre' => $formation->titre,
                    // Autres attributs de formation
                    'progression' => [
                        'commencee' => !is_null($progression),
                        'pourcentage' => $progression ? $progression->pourcentage : 0,
                        'completed' => $progression ? $progression->completed : false,
                        'videos_regardees' => $progression ? $progression->videos_regardees : [],
                    ],
                    'total_videos' => $formation->videos->count()
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $formations
        ]);
    }
    //
    public function getFormationsEnCours(Request $request)
    {
        $user = $request->user();

        $formations = Formation::select('formations.*')
            ->join('user_formations', 'formations.id', '=', 'user_formations.formation_id')
            ->leftJoin('progressions', function($join) use ($user) {
                $join->on('formations.id', '=', 'progressions.formation_id')
                     ->where('progressions.user_id', '=', $user->id);
            })
            ->where('user_formations.user_id', $user->id)
            ->where(function($query) {
                $query->whereHas('progressions', function($q) {
                    $q->where('pourcentage', '<', 100)
                      ->orWhere('completed', false);
                })
                ->orWhereDoesntHave('progressions');
            })
            ->with(['videos'])
            ->get()
            ->map(function ($formation) use ($user) {
                $progression = $formation->progressions()
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $formation->id,
                    'titre' => $formation->nom_formation,
                    'image' => $formation->image ? asset('storage/' . $formation->image) : null,
                    'progression' => [
                        'commencee' => !is_null($progression),
                        'pourcentage' => $progression ? $progression->pourcentage : 0,
                        'completed' => $progression ? $progression->completed : false,
                        'videos_regardees' => $progression ? $progression->videos_regardees : [],
                    ],
                    'total_videos' => $formation->videos->count()
                ];
            });

        if ($formations->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Vous n\'avez aucune formation en cours',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Formations en cours récupérées avec succès',
            'data' => $formations
        ]);
    }
}
