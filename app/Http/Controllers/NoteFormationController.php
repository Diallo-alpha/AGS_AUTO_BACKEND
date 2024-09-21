<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteFormationRequest;
use App\Http\Requests\UpdateNoteFormationRequest;
use App\Models\NoteFormation;
use App\Models\Formation;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;

class NoteFormationController extends Controller
{
    public function store(StoreNoteFormationRequest $request)
    {
        $formation = Formation::findOrFail($request->formation_id);

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

        // Vérifier si l'utilisateur a acheté la formation (paiement validé)
        $paiement = Paiement::where('formation_id', $formation->id)
            ->where('user_id', $user->id)
            ->where('status_paiement', 'payé') // S'assurer que le paiement est validé
            ->first();

        if (!$paiement) {
            return false; // L'utilisateur n'a pas acheté la formation
        }

        // Vérifier si l'utilisateur a terminé la formation
        $progression = $user->progressions()
            ->where('formation_id', $formation->id)
            ->where('terminer', true)
            ->first();

        return $progression ? true : false;
    }
}
