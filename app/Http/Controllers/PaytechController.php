<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Http\Request;
use App\Services\PaytechService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Notifications\PaymentSuccessNotification;
use Exception;


class PaytechController extends Controller
{
    protected $payTechService;

    public function __construct(PaytechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    /**
     * Récupère et affiche les paiements réussis.
     */
    public function getSuccessfulPayments()
    {
        Log::info('Tentative de récupération des paiements réussis');
        $payments = $this->payTechService->getSuccessfulPayments();

        if ($payments) {
            Log::info('Paiements réussis récupérés avec succès', ['payments' => $payments]);
        } else {
            Log::error('Échec de la récupération des paiements réussis');
        }

        return view('payments.success', [
            'payments' => $payments ?? null,
            'error' => $payments ? null : 'Impossible de récupérer les paiements'
        ]);
    }

    /**
     * Initie un paiement via Paytech.
     */
    public function initiatePayment(Request $request)
    {
        Log::info('Tentative d\'initialisation d\'un paiement', ['request_data' => $request->all()]);

        $validatedData = $request->validate([
            'item_name' => 'required|string|max:255',
            'item_price' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'formation_id' => 'required|exists:formations,id',
        ]);

        $user = Auth::user();
        $formation = Formation::findOrFail($validatedData['formation_id']);
        $transaction_id = "ags-{$user->id}_formation_{$formation->id}_" . uniqid();

        Log::info('Création du paiement pour', ['user_id' => $user->id, 'formation_id' => $formation->id]);

        $payTech = new PaytechService(env('PAYTECH_API_KEY'), env('PAYTECH_API_SECRET'));

        $payTech->setQuery([
            'item_name' => $validatedData['item_name'],
            'item_price' => $validatedData['item_price'],
            'command_name' => "Paiement pour {$validatedData['item_name']}",
        ])
        ->setRefCommand($transaction_id)
        ->setCurrency($validatedData['currency'])
        ->setNotificationUrl([
            'ipn_url' => env('PAYTECH_IPN_URL'),
            'success_url' => env('PAYTECH_SUCCESS_URL'),
            'cancel_url' => env('PAYTECH_CANCEL_URL'),
        ]);

        $response = $payTech->send();

        if ($response['success'] === 1) {
            Log::info('Paiement initié avec succès', ['transaction_id' => $transaction_id]);

            try {
                // Stockage initial du paiement dans la base de données
                $paiement = Paiement::create([
                    'reference' => $transaction_id,
                    'formation_id' => $formation->id,
                    'user_id' => $user->id,
                    'date_paiement' => now(),
                    'montant' => $validatedData['item_price'],
                    'mode_paiement' => 'wave',
                    'validation' => false,
                    'status_paiement' => 'en attente',
                ]);

                Log::info('Informations de paiement initiales stockées dans la base de données', [
                    'transaction_id' => $transaction_id,
                    'paiement_id' => $paiement->id
                ]);
            } catch (Exception $e) {
                Log::error('Erreur lors de la création du paiement dans la base de données', [
                    'transaction_id' => $transaction_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            Log::error('Échec de l\'initialisation du paiement', ['errors' => $response['errors']]);
        }

        return response()->json([
            'success' => $response['success'] === 1,
            'redirect_url' => $response['success'] === 1 ? $response['redirect_url'] : null,
            'errors' => $response['success'] === 1 ? null : $response['errors']
        ], $response['success'] === 1 ? 200 : 400);
    }
    /**
     * Vérifie la signature Paytech pour sécuriser la notification.
     */
    private function verifyPaytechSignature(Request $request)
    {
        Log::info('Vérification de la signature Paytech', ['request_headers' => $request->headers->all()]);

        $receivedSignature = $request->header('X-Paytech-Signature');
        if (!$receivedSignature) {
            Log::warning('Signature Paytech manquante dans la requête');
            return false;
        }

        $payload = $request->getContent();
        $secretKey = env('PAYTECH_SECRET_KEY');
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

        if ($receivedSignature !== $expectedSignature) {
            Log::warning('Signature Paytech invalide', [
                'received' => $receivedSignature,
                'expected' => $expectedSignature
            ]);
            return false;
        }

        Log::info('Signature Paytech valide');
        return true;
    }

    /**
     * Gère la notification de paiement reçue de Paytech.
     */
    public function handleNotification(Request $request)
    {
        Log::info('Notification PayTech reçue', ['request_data' => $request->all()]);

        try {
            // Extraction et validation des données
            $formationId = $request->input('formationId');
            $status = $request->input('status');

            if (!$formationId || !isset($status)) {
                Log::error('Données de notification incomplètes', [
                    'formationId' => $formationId,
                    'status' => $status
                ]);
                return response()->json(['error' => 'Données incomplètes'], 400);
            }

            Log::info('Données de notification validées', [
                'formationId' => $formationId,
                'status' => $status
            ]);

            // Récupération de la formation
            $formation = Formation::find($formationId);

            if (!$formation) {
                Log::error('Formation non trouvée', ['formation_id' => $formationId]);
                return response()->json(['error' => 'Formation non trouvée'], 404);
            }

            // Récupération du dernier paiement non validé pour cette formation
            $paiement = Paiement::where('formation_id', $formationId)
                                ->where('validation', false)
                                ->orderBy('created_at', 'desc')
                                ->first();

            if (!$paiement) {
                Log::error('Paiement non trouvé pour la formation', ['formation_id' => $formationId]);
                return response()->json(['error' => 'Paiement non trouvé'], 404);
            }

            // Mise à jour du paiement
            $paiement->validation = $status;
            $paiement->status_paiement = $status ? 'payé' : 'échoué';
            $paiement->save();

            Log::info('Paiement mis à jour', ['paiement_id' => $paiement->id, 'status' => $paiement->status_paiement]);

            // Mise à jour du rôle de l'utilisateur si le paiement est réussi
            if ($status) {
                $user = User::find($paiement->user_id);
                $user->role = 'etudiant';
                $user->formation_id = $formationId;
                $user->save();
                Log::info('Rôle de l\'utilisateur mis à jour', ['user_id' => $user->id, 'nouveau_role' => 'etudiant']);

                // Envoi de la notification
                try {
                    $user->notify(new PaymentSuccessNotification($paiement, $formation));
                    Log::info('Notification de paiement envoyée', ['user_id' => $user->id]);
                } catch (\Exception $e) {
                    Log::error('Erreur lors de l\'envoi de la notification', ['error' => $e->getMessage()]);
                }
            }

            return response()->json(['success' => true, 'message' => 'Paiement traité avec succès']);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement de la notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Erreur interne du serveur'], 500);
        }
    }


    /**
     * Affiche la page de succès après un paiement réussi.
     */
    public function paymentSuccess(Request $request)
    {
        Log::info('Affichage de la page de succès de paiement', ['request_data' => $request->all()]);

        try {
            $user = Auth::user();
            $formation = Formation::find($request->input('formation_id'));

            return view('payments.success', compact('user', 'formation'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la page de succès', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Erreur lors du traitement de la demande']);
        }
    }
}
