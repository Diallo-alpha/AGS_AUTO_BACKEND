<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use App\Models\Paiement;
use App\Models\User;
use App\Notifications\PaymentSuccessNotification;
use App\Services\PaytechService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaytechController extends Controller
{
    protected $payTechService;
    private const SUCCESS_REDIRECT_URL = 'https://admirable-macaron-cbfcb1.netlify.app';

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
                $paiement = Paiement::where('reference', $ref_command)->first();

                if (!$paiement) {
                    Log::warning('Paiement non trouvé pour la référence', ['ref_command' => $ref_command]);
                    return response()->json(['error' => 'Paiement non trouvé'], 404);
                }

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
    public function handleSuccessfulPayment(Paiement $paiement)
    {
        Log::info('Traitement d\'un paiement réussi', ['payment_id' => $paiement->id]);

        $user = User::find($paiement->user_id);
        $formation = Formation::find($paiement->formation_id);

        if (!$user || !$formation) {
            Log::error('Utilisateur ou formation non trouvé pour le paiement réussi', [
                'payment_id' => $paiement->id,
                'user_id' => $paiement->user_id,
                'formation_id' => $paiement->formation_id
            ]);
            return;
        }

        try {
            // Ajout de l'utilisateur à la formation
            DB::table('user_formations')->updateOrInsert(
                ['user_id' => $user->id, 'formation_id' => $formation->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $user->assignRole('etudiant');
            $user->save();

            Log::info('Rôle étudiant assigné à l\'utilisateur', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()
            ]);

            $user->notify(new PaymentSuccessNotification($paiement, $formation));

            Log::info('Utilisateur mis à jour, ajouté à la formation et notifié', [
                'user_id' => $user->id,
                'formation_id' => $formation->id
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de l\'utilisateur ou de l\'envoi de la notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
    public function paymentSuccess(Request $request)
    {
        Log::info('Affichage de la page de succès de paiement', ['request_data' => $request->all()]);

        try {
            $user = Auth::user();
            if (!$user) {
                Log::warning('Tentative d\'accès à la page de succès sans authentification');
                return redirect()->route('login')->with('error', 'Veuillez vous connecter pour voir les détails de votre paiement.');
            }

            $formationId = $request->input('formation_id');
            $transactionId = $request->input('ref_payment');

            if (!$formationId && !$transactionId) {
                Log::error('Formation ID et Transaction ID manquants dans la requête de succès');
                return redirect()->route('home')->with('error', 'Informations de paiement manquantes.');
            }

            $paiement = Paiement::where('user_id', $user->id)
                                ->when($formationId, function ($query) use ($formationId) {
                                    return $query->where('formation_id', $formationId);
                                })
                                ->when($transactionId, function ($query) use ($transactionId) {
                                    return $query->where('reference', $transactionId);
                                })
                                ->where('status_paiement', 'payé')
                                ->latest()
                                ->first();

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour l\'utilisateur', ['user_id' => $user->id, 'formation_id' => $formationId, 'transaction_id' => $transactionId]);
                return redirect()->route('home')->with('error', 'Détails du paiement non trouvés.');
            }

            $formation = Formation::findOrFail($paiement->formation_id);

            Log::info('Paiement trouvé et confirmé', ['paiement_id' => $paiement->id, 'formation_id' => $formation->id]);

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
