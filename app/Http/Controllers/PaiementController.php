<?php
namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Services\PayTechService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    protected $payTechService;

    public function __construct(PayTechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    /**
     * Méthode pour initier un paiement
     */
    public function effectuerPaiement(Request $request)
    {
        Log::info('Requête de paiement reçue', ['data' => $request->all()]);

        if (!$this->payTechService->testApiKeys()) {
            return response()->json(['message' => 'Clés API PayTech invalides'], 500);
        }

        $validatedData = $request->validate([
            'montant' => 'required|numeric|min:100',
            'description' => 'required|string|max:255',
            'currency' => 'sometimes|string|in:XOF,USD,EUR',
            'success_url' => 'sometimes|url',
            'cancel_url' => 'sometimes|url',
        ]);

        $result = $this->payTechService->initiatePayment($validatedData);

        if ($result['success']) {
            $paiement = Paiement::create([
                'user_id' => Auth::id(),
                'montant' => $validatedData['montant'],
                'description' => $validatedData['description'],
                'currency' => $validatedData['currency'] ?? 'XOF',
                'transaction_ref' => $result['token'],
                'status_paiement' => 'en_attente',
            ]);

            return response()->json([
                'message' => 'Paiement initié avec succès',
                'redirect_url' => $result['redirect_url'],
                'paiement_id' => $paiement->id
            ]);
        } else {
            Log::error('Échec de l\'initialisation du paiement', ['error' => $result['errors']]);
            return response()->json([
                'message' => 'Échec de l\'initialisation du paiement',
                'error' => $result['errors']
            ], 400);
        }
    }

    /**
     * Callback pour le paiement réussi
     */
    public function paymentSuccess(Request $request)
    {
        $token = $request->query('token');
        $paiement = Paiement::where('transaction_ref', $token)->first();

        if ($paiement) {
            $paiement->update([
                'status_paiement' => 'payé',
                'validation' => true,
            ]);

            return redirect()->route('formations.index')->with('success', 'Paiement validé avec succès!');
        } else {
            return redirect()->route('formations.index')->with('error', 'Paiement non trouvé.');
        }
    }

    /**
     * Méthode de gestion des annulations de paiements
     */
    public function paymentCancel(Request $request)
    {
        $token = $request->query('token');
        $paiement = Paiement::where('transaction_ref', $token)->first();

        if ($paiement) {
            $paiement->update(['status_paiement' => 'annulé']);
        }

        return redirect()->route('formations.index')->with('error', 'Paiement annulé.');
    }

    public function inscrire(Request $request, $formationId)
    {
        $request->validate([
            'mode_paiement' => 'required|in:wave,orange_money,free',
        ]);

        $formation = Formation::findOrFail($formationId);
        $user = Auth::user();

        if (!$user->hasRole('etudiant')) {
            $user->assignRole('etudiant');
        }

        $paiement = Paiement::create([
            'formation_id' => $formation->id,
            'user_id' => $user->id,
            'date_paiement' => now(),
            'montant' => $formation->prix,
            'mode_paiement' => $request->mode_paiement,
            'status_paiement' => 'en attente',
        ]);

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

        if ($payTechResponse['success']) {
            $paiement->update(['transaction_ref' => $payTechResponse['token']]);
            return redirect($payTechResponse['redirect_url']);
        } else {
            Log::error('Erreur lors de la demande de paiement', ['error' => $payTechResponse['errors']]);
            return back()->withErrors('Erreur lors de la demande de paiement : ' . ($payTechResponse['errors'] ?? 'Erreur inconnue.'));
        }
    }
}
