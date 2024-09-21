<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgressionRequest;
use App\Models\Progression;
use App\Models\Formation;
use Illuminate\Support\Facades\Auth;

class ProgressionController extends Controller
{


    // Créer ou mettre à jour une progression
    public function store(StoreProgressionRequest $request)
    {
        $user = Auth::user();

        // Création ou mise à jour de la progression
        $progression = Progression::updateOrCreate(
            ['formation_id' => $request->formation_id, 'user_id' => $user->id],
            ['pourcentage' => $request->pourcentage, 'completed' => $request->pourcentage == 100]
        );

        return response()->json(['message' => 'Progression mise à jour avec succès', 'data' => $progression], 200);
    }

    // Lire la progression d'un utilisateur sur une formation
    public function show($formationId)
    {
        $user = Auth::user();

        $progression = Progression::where('formation_id', $formationId)
                                  ->where('user_id', $user->id)
                                  ->first();

        if (!$progression) {
            return response()->json(['message' => 'Progression non trouvée.'], 404);
        }

        return response()->json(['progression' => $progression], 200);
    }

    // Mettre à jour une progression
    public function update(StoreProgressionRequest $request, $id)
    {
        $progression = Progression::findOrFail($id);

        // Vérification que l'utilisateur est bien celui qui a la progression
        if ($progression->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à modifier cette progression.'], 403);
        }

        $progression->update([
            'pourcentage' => $request->pourcentage,
            'completed' => $request->pourcentage == 100,
        ]);

        return response()->json(['message' => 'Progression mise à jour avec succès', 'data' => $progression], 200);
    }
}
