<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Services\PayTechService;
use Illuminate\Support\Facades\Auth;

class PaiementController extends Controller
{
    protected $payTechService;

    public function __construct(PayTechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    public function effectuerPaiement(Request $request)
    {
        $data = [
            'amount' => $request->montant,
            'currency' => 'XOF',
            'description' => 'Paiement de formation',
            'callback_url' => route('paiement.callback'),
        ];

        $result = $this->payTechService->initiatePayment($data);

        if ($result['success']) {
            return response()->json(['message' => 'Paiement initié avec succès', 'data' => $result], 200);
        } else {
            return response()->json(['message' => 'Échec du paiement', 'error' => $result['message']], 400);
        }
    }

    public function inscrire(Request $request, $formationId)
    {
        $request->validate([
            'mode_paiement' => 'required|in:wave,orange_money,free',
        ]);

        $formation = Formation::findOrFail($formationId);
        $user = Auth::user();

        // Créer un paiement
        $paiement = Paiement::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
            'date_paiement' => now(),
            'montant' => $formation->prix,
            'mode_paiement' => $request->mode_paiement,
            'status_paiement' => 'en attente',
        ]);

        // Logique d'appel à l'API PayTech pour le paiement
        $paymentData = [
            'item_name' => $formation->nom_formation,
            'item_price' => $formation->prix,
            'ref_command' => $paiement->id,
            'currency' => 'XOF',
            'mode_paiement' => $request->mode_paiement,
            'success_url' => route('payment.success', ['id' => $paiement->id]),
            'cancel_url' => route('payment.cancel', ['id' => $paiement->id]),
        ];

        $payTechResponse = $this->payTechService->initiatePayment($paymentData);

        if ($payTechResponse['success']) {
            // Mettre à jour la référence de transaction PayTech
            $paiement->update(['reference_paiement' => $payTechResponse['transaction_id']]);

            return redirect($payTechResponse['redirect_url']);
        } else {
            return back()->withErrors('Erreur lors de la demande de paiement.');
        }
    }

    public function paymentSuccess(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);

        // Mise à jour après succès du paiement
        $paiement->update([
            'status_paiement' => 'payé',
            'validation' => true,
        ]);

        // Mise à jour de la formation avec l'utilisateur payé
        $formation = Formation::findOrFail($paiement->formation_id);
        $formation->update([
            'user_id' => $paiement->user_id
        ]);

        return redirect()->route('formations.index')->with('success', 'Paiement validé et formation inscrite avec succès!');
    }

    public function paymentCancel(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->update(['status_paiement' => 'annulé']);

        return redirect()->route('formations.index')->with('error', 'Paiement annulé.');
    }
}
