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

        $apiKey = env('PAYTECH_API_KEY', '3e80a4c267a89a4fb9c8ee8cd93d7c06fe1362a43f6188d396cc543631585abd');
        $apiSecret = env('PAYTECH_API_SECRET', '0ff8d65e5c9c6a8e3b839d6b8065ed1384ceb9b037ad6cf31effe7504d3d7c14');

        $payTech = new PaytechService($apiKey, $apiSecret);

        $payTech->setQuery([
            'item_name' => $validatedData['item_name'],
            'item_price' => $validatedData['item_price'],
            'command_name' => "Paiement pour {$validatedData['item_name']}",
        ])
        ->setRefCommand($transaction_id)
        ->setCurrency($validatedData['currency'])
        ->setNotificationUrl([
            'ipn_url' => route('paytech.notification'),
            'success_url' => route('payment.success'),
            'cancel_url' => route('paytech.cancel'),
        ]);

        $response = $payTech->send();

        if ($response['success'] === 1) {
            Log::info('Paiement initié avec succès', ['transaction_id' => $transaction_id]);

            try {
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

    public function handleNotification(Request $request)
    {
        Log::info('Notification PayTech reçue', ['request_data' => $request->all()]);

        $type_event = $request->input('type_event');
        $ref_command = $request->input('ref_command');
        $item_price = $request->input('item_price');
        $payment_method = $request->input('payment_method');

        $my_api_key = env('PAYTECH_API_KEY', '3e80a4c267a89a4fb9c8ee8cd93d7c06fe1362a43f6188d396cc543631585abd');
        $my_api_secret = env('PAYTECH_API_SECRET', '0ff8d65e5c9c6a8e3b839d6b8065ed1384ceb9b037ad6cf31effe7504d3d7c14');

        if (hash('sha256', $my_api_secret) === $request->input('api_secret_sha256') && hash('sha256', $my_api_key) === $request->input('api_key_sha256')) {
            Log::info('Notification validée comme provenant de PayTech');

            try {
                $paiement = Paiement::where('reference', $ref_command)->firstOrFail();
                $paiement->status_paiement = $this->getPaymentStatus($type_event);
                $paiement->montant = $item_price;
                $paiement->mode_paiement = $this->mapPaymentMethod($payment_method);
                $paiement->validation = $paiement->status_paiement === 'payé';
                $paiement->save();

                Log::info('Paiement mis à jour', ['payment_id' => $paiement->id, 'status' => $paiement->status_paiement]);

                if ($paiement->status_paiement === 'payé') {
                    $this->handleSuccessfulPayment($paiement);
                }

                return response()->json(['success' => true, 'message' => 'Paiement traité avec succès']);
            } catch (\Exception $e) {
                Log::error('Erreur lors du traitement de la notification', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Échec du traitement du paiement'], 500);
            }
        } else {
            Log::warning('Notification non valide - Signature incorrecte');
            return response()->json(['error' => 'Signature invalide'], 400);
        }
    }

    private function getPaymentStatus($type_event)
    {
        switch ($type_event) {
            case 'sale_complete':
                return 'payé';
            case 'sale_canceled':
                return 'annulé';
            default:
                return 'en attente';
        }
    }

    private function mapPaymentMethod($payTechMethod)
    {
        $methodMap = [
            'Carte Bancaire' => 'carte',
            'PayPal' => 'paypal',
            'Orange Money' => 'orange_money',
            'Joni Joni' => 'joni_joni',
            'Wari' => 'wari',
            'Poste Cash' => 'poste_cash',
            'Wave' => 'wave'
        ];

        return $methodMap[$payTechMethod] ?? 'autre';
    }

    public function handleSuccessfulPayment(Request $request, $paiement = null)
    {
        if (!$paiement) {
            $transactionId = $request->input('ref_payment');
            $paiement = Paiement::where('reference', $transactionId)->first();

            if (!$paiement) {
                Log::error('Paiement non trouvé pour la référence', ['ref_payment' => $transactionId]);
                return redirect()->route('home')->with('error', 'Paiement non trouvé.');
            }
        }

        Log::info('Traitement d\'un paiement réussi', ['payment_id' => $paiement->id]);

        $user = User::find($paiement->user_id);
        $formation = Formation::find($paiement->formation_id);

        if ($user && $formation) {
            $user->formation_id = $formation->id;
            $user->role = 'etudiant';
            $user->save();

            $user->notify(new PaymentSuccessNotification($paiement, $formation));

            Log::info('Utilisateur mis à jour et notifié', ['user_id' => $user->id, 'formation_id' => $formation->id]);

            return redirect()->route('payment.success', ['formation_id' => $formation->id])
                             ->with('success', 'Paiement traité avec succès.');
        } else {
            Log::warning('Utilisateur ou formation non trouvé pour le paiement réussi', ['payment_id' => $paiement->id]);
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors du traitement du paiement.');
        }
    }
    public function paymentCancel(Request $request, $id)
    {
        Log::info('Payment cancelled', ['payment_id' => $id]);
        $payment = Paiement::findOrFail($id);
        $payment->status_paiement = 'annulé';
        $payment->save();

        return redirect()->route('home')->with('error', 'Le paiement a été annulé.');
    }

    public function verifyPayment($transactionId)
    {
        Log::info('Verifying payment', ['transaction_id' => $transactionId]);
        $payment = Paiement::where('reference', $transactionId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        return response()->json([
            'status' => $payment->status_paiement,
            'amount' => $payment->montant,
            'date' => $payment->date_paiement,
        ]);
    }

    public function paymentSuccess(Request $request)
    {
        Log::info('Affichage de la page de succès de paiement', ['request_data' => $request->all()]);

        try {
            $user = Auth::user();
            $formationId = $request->input('formation_id');

            if (!$user || !$formationId) {
                throw new Exception('Utilisateur non authentifié ou formation non spécifiée.');
            }

            $formation = Formation::findOrFail($formationId);
            $paiement = Paiement::where('user_id', $user->id)
                                ->where('formation_id', $formationId)
                                ->where('status_paiement', 'payé')
                                ->latest()
                                ->firstOrFail();

            return view('payments.success', compact('user', 'formation', 'paiement'));
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'affichage de la page de succès', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors de l\'affichage de la page de succès.');
        }
    }
}
