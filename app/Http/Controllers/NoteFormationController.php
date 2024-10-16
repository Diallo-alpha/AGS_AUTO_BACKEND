<?php

namespace App\Http\Controllers;

use Log;
use App\Http\Requests\StoreNoteFormationRequest;
use App\Http\Requests\UpdateNoteFormationRequest;
use App\Models\Formation;
use App\Models\NoteFormation;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;

class NoteFormationController extends Controller
{
    public function store(StoreNoteFormationRequest $request)
    {
        $formation = Formation::findOrFail($request->formation_id);
        Log::info('Données reçues:', $request->all());

        // Vérifier si l'utilisateur a acheté et terminé la formation
        if (!$this->userHasCompletedFormation($formation)) {
            return response()->json(['message' => 'Vous devez acheter et terminer la formation pour pouvoir la noter.'], 403);
        }

        // Vérifier si l'utilisateur a déjà noté cette formation
        $existingNote = NoteFormation::where('formation_id', $formation->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingNote) {
            return response()->json(['message' => 'Vous avez déjà noté cette formation.'], 422);
        }

        $noteFormation = NoteFormation::create([
            'formation_id' => $formation->id,
            'user_id' => Auth::id(),
            'note' => $request->note,
            'avis' => $request->avis,
        ]);

        return response()->json(['message' => 'Votre note et avis ont été enregistrés avec succès.', 'data' => $noteFormation], 201);
    }

    public function update(UpdateNoteFormationRequest $request, NoteFormation $noteFormation)
    {
        if ($noteFormation->user_id !== Auth::id()) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à modifier cette note.'], 403);
        }

        $noteFormation->update([
            'note' => $request->note,
            'avis' => $request->avis,
        ]);

        return response()->json(['message' => 'Votre note et avis ont été mis à jour avec succès.', 'data' => $noteFormation]);
    }

    // Méthode pour vérifier si l'utilisateur a acheté et terminé la formation
    private function userHasCompletedFormation(Formation $formation)
    {
        $user = Auth::user();

        // Vérifier si l'utilisateur a terminé la formation
        $progression = $user->progressions()
            ->where('formation_id', $formation->id)
            ->where('completed', true)
            ->first();

        return $progression !== null;
    }
    //afficher les commentaire d'une formation
    public function showFormationAvis($formationId)
    {
        $avis = NoteFormation::where('formation_id', $formationId)
            ->with(['user:id,nom_complet,photo'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return response()->json([
            'avis' => $avis->map(function ($avis) {
                return [
                    'id' => $avis->id,
                    'note' => $avis->note,
                    'avis' => $avis->avis,
                    'created_at' => $avis->created_at,
                    'user' => [
                        'id' => $avis->user->id,
                        'nom_complet' => $avis->user->nom_complet,
                        'photo' => $avis->user->photo ? url('storage/' . $avis->user->photo) : null,
                    ],
                ];
            }),
        ]);
    }
}
