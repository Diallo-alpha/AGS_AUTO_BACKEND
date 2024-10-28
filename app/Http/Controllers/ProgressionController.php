<?php

namespace App\Http\Controllers;

use App\Models\Progression;
use App\Models\Formation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProgressionController extends Controller
{
    public function marquerVideoCommeVue(Request $request)
    {
        $user = Auth::user();
        $videoId = $request->input('video_id');
        $formationId = $request->input('formation_id');

        $progression = Progression::firstOrCreate(
            ['formation_id' => $formationId, 'user_id' => $user->id],
            ['pourcentage' => 0, 'completed' => false, 'videos_regardees' => []]
        );

        $progression->marquerVideoCommeVue($videoId);

        return response()->json([
            'message' => 'Progression mise à jour avec succès',
            'data' => [
                'pourcentage' => $progression->pourcentage,
                'completed' => $progression->completed,
                'videos_regardees' => $progression->videos_regardees
            ]
        ], 200);
    }

    public function show($formationId)
    {
        $user = Auth::user();

        $progression = Progression::where('formation_id', $formationId)
                                  ->where('user_id', $user->id)
                                  ->first();

        if (!$progression) {
            return response()->json([
                'message' => 'Progression non trouvée.',
                'data' => [
                    'pourcentage' => 0,
                    'completed' => false,
                    'videos_regardees' => []
                ]
            ], 200);
        }

        return response()->json([
            'message' => 'Progression récupérée avec succès',
            'data' => [
                'pourcentage' => $progression->pourcentage,
                'completed' => $progression->completed,
                'videos_regardees' => $progression->videos_regardees
            ]
        ], 200);
    }
   /**
     * Récupère toutes les formations complétées par l'utilisateur
     */
    public function getFormationsTerminees()
    {
        $user = Auth::user();

        $formationsTerminees = Formation::select('formations.*')
            ->join('progressions', 'formations.id', '=', 'progressions.formation_id')
            ->where('progressions.user_id', $user->id)
            ->where('progressions.pourcentage', 100)
            ->where('progressions.completed', true)
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Formations terminées récupérées avec succès',
            'data' => [
                'total' => $formationsTerminees->count(),
                'formations' => $formationsTerminees->map(function ($formation) {
                    $progression = $formation->progressions()
                        ->where('user_id', Auth::id())
                        ->first();

                    return [
                        'formation' => [
                            'id' => $formation->id,
                            'nom_formation' => $formation->nom_formation,
                            'description' => $formation->description,
                            'image' => $formation->image ? asset('storage/' . $formation->image) : null,
                        ],
                        'progression' => [
                            'pourcentage' => $progression->pourcentage,
                            'terminer' => $progression->completed,
                            'date_completion' => $progression->updated_at->format('Y-m-d H:i:s')
                        ]
                    ];
                })
            ]
        ], 200);
    }
 /**
 * Récupère toutes les formations en cours pour l'utilisateur
 * (celles qui ont une progression mais pas à 100%)
 */
// public function getFormationsEnCours()
// {
//     $user = Auth::user();

//     // Utilisation de with() pour eager loading et éviter le N+1 problem
//     $formationsEnCours = Formation::with(['progressions' => function ($query) use ($user) {
//         $query->where('user_id', $user->id);
//     }])
//     ->whereHas('progressions', function ($query) use ($user) {
//         $query->where('user_id', $user->id)
//               ->where(function ($q) {
//                   $q->where('pourcentage', '<', 100)
//                     ->orWhere('completed', false);
//               });
//     })
//     ->get();

//     // Préparation de la réponse
//     $formattedFormations = $formationsEnCours->map(function ($formation) {
//         $progression = $formation->progressions->first();

//         return [
//             'formation' => [
//                 'id' => $formation->id,
//                 'titre' => $formation->titre,
//                 // Ajoutez d'autres champs de formation nécessaires ici
//             ],
//             'progression' => [
//                 'pourcentage' => $progression->pourcentage,
//                 'terminer' => $progression->completed,
//                 'date_derniere_activite' => $progression->updated_at,
//                 'videos_regardees' => $progression->videos_regardees
//             ]
//         ];
//     });

//     return response()->json([
//         'status' => 'success',
//         'message' => $formationsEnCours->count() > 0
//             ? 'Formations en cours récupérées avec succès'
//             : 'Vous n\'avez aucune formation en cours',
//         'data' => [
//             'total' => $formationsEnCours->count(),
//             'formations' => $formattedFormations
//         ]
//     ], 200);
// }
}
