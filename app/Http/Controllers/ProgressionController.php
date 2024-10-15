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
}
