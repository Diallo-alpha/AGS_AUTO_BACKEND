<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\Progression;
use Illuminate\Http\Request;
use Barryvdh\Snappy\Facades\SnappyPdf;

class CertificateController extends Controller
{
    public function generate(Request $request, $formationId)
    {
        $user = $request->user();
        $formation = Formation::findOrFail($formationId);
        $progression = Progression::where('user_id', $user->id)
                                  ->where('formation_id', $formationId)
                                  ->firstOrFail();

        if ($progression->pourcentage < 100) {
            return response()->json(['message' => 'Vous n\'avez pas terminé la formation.'], 403);
        }

        $data = [
            'user' => $user,
            'formation' => $formation,
            'date' => now()->format('d/m/Y'),
        ];

        $pdf = SnappyPdf::loadView('welcome', $data);

        // Configuration pour s'assurer que les styles sont correctement appliqués
        $pdf->setOption('enable-local-file-access', true);
        $pdf->setOption('javascript-delay', 1000);
        $pdf->setOption('no-stop-slow-scripts', true);
        $pdf->setOption('enable-smart-shrinking', true);

        return $pdf->download('certificat_' . $formation->nom_formation . '.pdf');
    }
}
