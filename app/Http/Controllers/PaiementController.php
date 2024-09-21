<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Services\PayTechService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StorePaiementRequest;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    protected $payTechService;

    public function __construct(PayTechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

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

        if ($result['success']) {
            return response()->json(['message' => 'Paiement initié avec succès', 'data' => $result], 200);
        } else {
            return response()->json([
                'message' => 'Échec du paiement',
                'error' => $result['errors'] ?? 'Erreur interne'
            ], 400);
        }
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
            return back()->withErrors('Erreur lors de la demande de paiement : ' . ($payTechResponse['errors'] ?? 'Erreur inconnue.'));
        }
    }

    public function paymentSuccess(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->update([
            'status_paiement' => 'payé',
            'validation' => true,
        ]);

        $user = Auth::user();
        if (!$user->hasRole('etudiant')) {
            $user->assignRole('etudiant');
        }

        return redirect()->route('formations.index')->with('success', 'Paiement validé avec succès!');
    }

    public function paymentCancel(StorePaiementRequest $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->update(['status_paiement' => 'annulé']);

        return redirect()->route('formations.index')->with('error', 'Paiement annulé.');
    }

    public function handleIPN(Request $request)
    {
        $type_event = $request->input('type_event');
        $custom_field = json_decode($request->input('custom_field'), true);
        $ref_command = $request->input('ref_command');
        $item_name = $request->input('item_name');
        $item_price = $request->input('item_price');
        $devise = $request->input('devise');
        $command_name = $request->input('command_name');
        $env = $request->input('env');
        $token = $request->input('token');
        $api_key_sha256 = $request->input('api_key_sha256');
        $api_secret_sha256 = $request->input('api_secret_sha256');

        $my_api_key = config('services.paytech.api_key');
        $my_api_secret = config('services.paytech.api_secret');

        if (hash('sha256', $my_api_secret) === $api_secret_sha256 && hash('sha256', $my_api_key) === $api_key_sha256) {
            $paiement = Paiement::where('transaction_ref', $token)->first();
            if ($paiement) {
                $paiement->update([
                    'status_paiement' => 'payé',
                    'validation' => true,
                ]);
                Log::info('Paiement validé via IPN', ['paiement_id' => $paiement->id]);
                // Autres actions nécessaires (par exemple, envoi d'e-mail, mise à jour de l'inscription, etc.)
            }
            return response()->json(['status' => 'success']);
        } else {
            Log::warning('Tentative de notification IPN invalide', ['request' => $request->all()]);
            return response()->json(['status' => 'error', 'message' => 'Invalid request'], 400);
        }
    }
}
