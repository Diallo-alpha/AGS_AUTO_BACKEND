<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Services\PayTechService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePaiementRequest;

class PaiementController extends Controller
{
    protected $payTechService;

    public function __construct(PayTechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    // Nouvelle méthode pour récupérer les paiements réussis
    public function getSuccessfulPayments()
    {
        $payments = $this->payTechService->getSuccessfulPayments();

        if ($payments) {
            return view('payments.success', ['payments' => $payments]);
        } else {
            return view('payments.success', ['error' => 'Impossible de récupérer les paiements']);
        }
    }

    public function effectuerPaiement(Request $request)
    {
        $data = [
            'montant' => $request->montant,
            'currency' => 'XOF',
            'description' => 'Paiement de formation',
            'callback_url' => route('paiement.callback'),
        ];

        $result = $this->payTechService->initiatePayment($data);

        // Amélioration des messages d'erreur en cas d'échec
        if ($result['success']) {
            return response()->json(['message' => 'Paiement initié avec succès', 'data' => $result], 200);
        } else {
            return response()->json([
                'message' => 'Échec du paiement',
                'error' => $result['message'] ?? 'Erreur interne'
            ], 400);
        }
    }

    // Méthode d'inscription avec amélioration
    public function inscrire(Request $request, $formationId)
    {
        $request->validate([
            'mode_paiement' => 'required|in:wave,orange_money,free',
        ]);

        $formation = Formation::findOrFail($formationId);
        $user = Auth::user();

        // Vérifier si l'utilisateur a déjà le rôle d'étudiant et lui assigner si besoin
        if (!$user->hasRole('etudiant')) {
            $user->assignRole('etudiant');
        }

        // Créer un paiement
        $paiement = Paiement::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
            'date_paiement' => now(),
            'montant' => $formation->prix,
            'mode_paiement' => $request->mode_paiement,
            'status_paiement' => 'en attente',
        ]);

        // Préparer les données pour PayTech
        $paymentData = [
            'item_name' => $formation->titre,
            'item_price' => $formation->prix,
            'ref_command' => $paiement->id,
            'currency' => 'XOF',
            'mode_paiement' => $request->mode_paiement,
            'success_url' => route('payment.success', ['id' => $paiement->id]),
            'cancel_url' => route('payment.cancel', ['id' => $paiement->id]),
        ];

        $payTechResponse = $this->payTechService->initiatePayment($paymentData);

        // Redirection en cas de succès, sinon afficher une erreur améliorée
        if ($payTechResponse['success']) {
            $paiement->update(['transaction_ref' => $payTechResponse['transaction_id']]);
            return redirect($payTechResponse['redirect_url']);
        } else {
            return back()->withErrors('Erreur lors de la demande de paiement : ' . ($payTechResponse['errors'] ?? 'Erreur inconnue.'));
        }
    }

    // Gestion de la validation de paiement réussie
    public function paymentSuccess(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->update([
            'status_paiement' => 'payé',
            'validation' => true,
        ]);

        // Amélioration : envoyer une notification à l'utilisateur ou le rôle
        $user = Auth::user();
        if (!$user->hasRole('etudiant')) {
            $user->assignRole('etudiant');
        }

        return redirect()->route('formations.index')->with('success', 'Paiement validé avec succès!');
    }

    // Gestion de l'annulation de paiement
    public function paymentCancel(StorePaiementRequest $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->update(['status_paiement' => 'annulé']);

        return redirect()->route('formations.index')->with('error', 'Paiement annulé.');
    }
}
