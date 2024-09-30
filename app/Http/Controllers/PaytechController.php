<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Services\PaytechService;
use App\Models\Payment;

class PaytechController extends Controller
{
    protected $payTechService;

    public function __construct(PaytechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    public function getSuccessfulPayments()
    {
        $payments = $this->payTechService->getSuccessfulPayments();

        if ($payments) {
            return view('payments.success', ['payments' => $payments]);
        } else {
            return view('payments.success', ['error' => 'Unable to fetch payments']);
        }
    }
    public function initiatePayment(Request $request)
    {
        // Valider les données de la requête
        $validatedData = $request->validate([
            'item_name' => 'required|string|max:255',
            'item_price' => 'required|numeric',
            'currency' => 'required|string|size:3',
        ]);

        // Générer un identifiant unique pour la transaction
        $transaction_id = uniqid();
        $transaction_id_full = "xeeweule-" . $transaction_id;

        // Récupérer les clés API PayTech depuis le fichier .env
        $apiKey = env('PAYTECH_API_KEY');
        $apiSecret = env('PAYTECH_API_SECRET');

        // Instancier l'objet PayTech avec les clés API
        $payTech = new PaytechService($apiKey, $apiSecret);

        // Définir les paramètres de la requête
        $payTech->setQuery([
            'item_name' => $validatedData['item_name'],
            'item_price' => $validatedData['item_price'],
            'command_name' => 'Payment for ' . $validatedData['item_name'],
        ])
        ->setRefCommand($transaction_id_full)
        ->setCurrency($validatedData['currency'])
        ->setNotificationUrl([
            'ipn_url' => env('PAYTECH_IPN_URL'),
            'success_url' => env('PAYTECH_SUCCESS_URL'),
            'cancel_url' => env('PAYTECH_CANCEL_URL'),
        ]);

        // Envoyer la requête de paiement
        $response = $payTech->send();
        // dd($response);

        // Vérifier la réponse et rediriger l'utilisateur
        if ($response['success'] === 1) {
            return redirect($response['redirect_url']);
        } else {
            return redirect()->back()->with('error', $response['errors']);
        }
    }

    public function handleNotification(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $paymentStatus = $request->input('status');

        // Mettre à jour le statut du paiement en base de données
        $payment = Paiement::where('transaction_id', $paymentId)->first();
        if ($payment) {
            $payment->update(['status' => $paymentStatus]);

            if ($paymentStatus == 'success') {
                // Générer un identifiant et mot de passe pour MikroTik
                $username = 'user_' . \Str::random(8);
                $password = \Str::random(12);

                // Mettre à jour les informations de paiement avec les identifiants MikroTik
                $payment->update([
                    'mikrotik_username' => $username,
                    'mikrotik_password' => $password,
                ]);

                // Envoi d'un email ou autre méthode pour informer l'utilisateur des informations
                // Mail::to($user->email)->send(new PaymentSuccess($username, $password));
            }
        }

        // Rediriger ou afficher une confirmation
        return response()->json(['success' => true]);
    }

    public function paymentSuccess()
    {
        return view('payment.success');
    }

    public function paymentCancel()
    {
        return view('payment.cancel');
    }

}

