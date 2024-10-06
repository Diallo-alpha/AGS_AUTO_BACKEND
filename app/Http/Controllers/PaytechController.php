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
            'success_url' => route('paytech.success'),
            'cancel_url' => route('paytech.cancel', ['ref_command' => $transaction_id]),
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

    private function verifyPaytechSignature(Request $request)
    {
        Log::info('Vérification de la signature Paytech', ['request_headers' => $request->headers->all()]);

        $receivedSignature = $request->header('X-Paytech-Signature');
        if (!$receivedSignature) {
            Log::warning('Signature Paytech manquante dans la requête');
            return false;
        }

        $payload = $request->getContent();
        $secretKey = env('PAYTECH_API_SECRET');
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

    public function handleNotification(Request $request)
    {
        Log::info('Notification PayTech reçue', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ]);
        // if (!$this->verifyPaytechSignature($request)) {
        //     Log::error('Signature PayTech invalide');
        //     return response()->json(['error' => 'Signature invalide'], 400);
        // }

        $paymentId = $request->input('ref_command');
        $paymentStatus = $request->input('type_event');
        $amount = $request->input('amount');
        $paymentMethod = $request->input('payment_method');

        Log::info('Détails de la notification', [
            'paymentId' => $paymentId,
            'status' => $paymentStatus,
            'amount' => $amount,
            'payment_method' => $paymentMethod
        ]);

        $refParts = explode('_', $paymentId);
        $userId = $refParts[1] ?? null;
        $formationId = $refParts[3] ?? null;

        $user = User::find($userId);
        $formation = Formation::find($formationId);

        if (!$user || !$formation) {
            Log::error('Utilisateur ou formation non trouvé', ['user_id' => $userId, 'formation_id' => $formationId]);
            return response()->json(['error' => 'Utilisateur ou formation invalide'], 400);
        }

        Log::info('Utilisateur et formation trouvés');

        try {
            Log::info('Tentative de mise à jour ou création du paiement');
            $paiement = Paiement::updateOrCreate(
                ['reference' => $paymentId],
                [
                    'formation_id' => $formation->id,
                    'user_id' => $user->id,
                    'date_paiement' => now(),
                    'montant' => $amount,
                    'mode_paiement' => $this->mapPaymentMethod($paymentMethod),
                    'validation' => $this->isPaymentComplete($paymentStatus),
                    'status_paiement' => $this->getPaymentStatus($paymentStatus),
                ]
            );

            Log::info('Paiement  mis à jour', ['payment_id' => $paiement->id]);

            if ($this->isPaymentComplete($paymentStatus)) {
                Log::info('Paiement complet détecté, appel de handleSuccessfulPayment');
                $this->handleSuccessfulPayment($paiement, $user, $formation);
            } else {
                Log::info('Paiement en attente ou échoué', [
                    'payment_id' => $paiement->id,
                    'status' => $paymentStatus
                ]);
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'enregistrement du paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Échec du traitement du paiement'], 500);
        }
        Log::info('Fin de handleNotification');
        return response()->json(['success' => true]);
    }

    private function isPaymentComplete($status)
    {
        $isComplete = $status === 'sale_complete';
        Log::info('Vérification du statut de paiement', ['status' => $status, 'isComplete' => $isComplete]);
        return $isComplete;
    }

    private function getPaymentStatus($status)
    {
        Log::info('Statut de paiement reçu', ['status' => $status]);
        switch ($status) {
            case 'sale_complete':
                return 'payé';
            case 'sale_canceled':
                return 'annulé';
            default:
                return 'en attente';
        }
    }

    private function handleSuccessfulPayment($payment, $user, $formation)
    {
        Log::info('Début du traitement d\'un paiement réussi', ['payment_id' => $payment->id]);

        try {
            // Mise à jour du paiement
            $payment->status_paiement = 'payé';
            $payment->validation = true;
            $payment->save();

            $user->formation_id = $formation->id;

            if ($formation) {
                $user->role = 'etudiant';
                Log::info('Rôle de l\'utilisateur mis à jour en étudiant', ['user_id' => $user->id]);
            }

            $user->save();

            Log::info('Formation de l\'utilisateur mise à jour', [
                'user_id' => $user->id,
                'formation_id' => $formation->id
            ]);

            $user->notify(new PaymentSuccessNotification($payment, $formation));

            Log::info('Notification de paiement envoyée à l\'utilisateur', ['user_email' => $user->email]);
            Log::info('Fin du traitement du paiement réussi', ['payment_id' => $payment->id]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement du paiement réussi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'formation_id' => $formation->id
            ]);
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

    private function mapPaymentMethod($payTechMethod)
    {
        $methodMap = [
            'wave' => 'wave',
            'orange_money' => 'orange_money',
            'free_money' => 'free'
        ];

        return $methodMap[$payTechMethod] ?? 'wave';
    }

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
