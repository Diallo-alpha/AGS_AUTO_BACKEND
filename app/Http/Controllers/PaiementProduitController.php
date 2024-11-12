<?php

namespace App\Http\Controllers;

use App\Models\Commande;
use App\Models\Paiement;
use App\Models\Paiement_produit;
use App\Models\Produit;
use App\Services\PaytechService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;

class PaiementProduitController extends Controller
{
    protected $payTechService;
    private const SUCCESS_REDIRECT_URL = 'https://admirable-macaron-cbfcb1.netlify.app';

    public function __construct(PaytechService $payTechService)
    {
        $this->payTechService = $payTechService;
    }

    /**
     * Initie le processus de paiement pour un ou plusieurs produits.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initierPaiement(Request $request)
    {
        Log::info('Tentative d\'initialisation d\'un paiement de produit', ['request_data' => $request->all()]);

        try {
            $user = auth()->userOrFail();
        } catch (JWTException $e) {
            Log::error('Utilisateur non authentifié lors de l\'initialisation du paiement');
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $validatedData = $request->validate([
            'produits' => 'required|array',
            'produits.*.id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
        ]);

        $montant_total = 0;
        $produits_commande = [];

        foreach ($validatedData['produits'] as $produit_data) {
            $produit = Produit::findOrFail($produit_data['id']);
            $montant_total += $produit->prix * $produit_data['quantite'];
            $produits_commande[] = [
                'produit_id' => $produit->id,
                'quantite' => $produit_data['quantite'],
                'prix_unitaire' => $produit->prix
            ];
        }

        $transaction_id = "produit-{$user->id}-" . uniqid();

        $paytech = new PaytechService(env('PAYTECH_API_KEY'), env('PAYTECH_API_SECRET'));
        $paytech->setQuery([
            'item_name' => 'Achat de produits',
            'item_price' => $montant_total,
            'command_name' => "Commande de produits",
        ])
        ->setRefCommand($transaction_id)
        ->setCurrency('XOF')
        ->setNotificationUrl([
            'ipn_url' => route('paiement.notification'),
            'success_url' => route('paiement.succes'),
            'cancel_url' => route('paiement.annulation'),
        ]);

        $response = $paytech->send();

        if ($response['success'] === 1) {
            Log::info('Paiement de produit initié avec succès', ['transaction_id' => $transaction_id]);

            try {
                DB::transaction(function () use ($user, $montant_total, $transaction_id, $produits_commande) {
                    $commande = Commande::create([
                        'user_id' => $user->id,
                        'somme' => $montant_total,
                        'status' => 'en attente',
                        'date' => now(),
                    ]);

                    $commande->produits()->attach($produits_commande);

                    Paiement_produit::create([
                        'commande_id' => $commande->id,
                        'montant' => $montant_total,
                        'mode_paiement' => 'en attente',
                        'reference' => $transaction_id,
                    ]);
                });
            } catch (Exception $e) {
                Log::error('Erreur lors de la création de la commande et du paiement', [
                    'transaction_id' => $transaction_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Erreur lors de l\'initialisation du paiement'], 500);
            }
        } else {
            Log::error('Échec de l\'initialisation du paiement de produit', ['errors' => $response['errors']]);
        }

        return response()->json([
            'success' => $response['success'] === 1,
            'redirect_url' => $response['success'] === 1 ? $response['redirect_url'] : null,
            'errors' => $response['success'] === 1 ? null : $response['errors']
        ], $response['success'] === 1 ? 200 : 400);
    }

    /**
     * Gère la notification de paiement envoyée par Paytech.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gererNotification(Request $request)
    {
        Log::info('Notification Paytech reçue pour paiement de produit', ['request_data' => $request->all()]);

        $type_event = $request->input('type_event');
        $ref_command = $request->input('ref_command');
        $payment_method = $request->input('payment_method');

        $my_api_key = env('PAYTECH_API_KEY', '3e80a4c267a89a4fb9c8ee8cd93d7c06fe1362a43f6188d396cc543631585abd');
        $my_api_secret = env('PAYTECH_API_SECRET', '0ff8d65e5c9c6a8e3b839d6b8065ed1384ceb9b037ad6cf31effe7504d3d7c14');

        if (hash('sha256', $my_api_secret) === $request->input('api_secret_sha256') && hash('sha256', $my_api_key) === $request->input('api_key_sha256')) {
            Log::info('Notification validée comme provenant de PayTech');

            try {
                DB::transaction(function () use ($ref_command, $type_event, $payment_method) {
                    $paiement = Paiement_produit::where('reference', $ref_command)->firstOrFail();
                    $commande = $paiement->commande;

                    $status_paiement = $this->obtenirStatutPaiement($type_event);
                    $paiement->mode_paiement = $this->mapperMethodePaiement($payment_method);
                    $paiement->save();

                    $commande->status = $status_paiement === 'payé' ? 'en attente de livraison' : 'annulé';
                    $commande->save();

                    if ($status_paiement === 'payé') {
                        $this->traiterPaiementReussi($commande);
                    }
                });

                Log::info('Paiement de produit mis à jour', ['ref_command' => $ref_command, 'status' => $status_paiement]);
                return response()->json(['success' => true, 'message' => 'Paiement traité avec succès']);
            } catch (Exception $e) {
                Log::error('Erreur lors du traitement de la notification de paiement de produit', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['error' => 'Échec du traitement du paiement'], 500);
            }
        } else {
            Log::warning('Notification de paiement de produit non valide - Signature incorrecte');
            return response()->json(['error' => 'Signature invalide'], 400);
        }
    }

    /**
     * Gère le retour de l'utilisateur après un paiement réussi.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function gererSuccesPaiement(Request $request)
    {
        Log::info('Traitement du succès de paiement de produit', ['request_data' => $request->all()]);

        try {
            $transaction_id = $request->input('ref_payment') ?? $request->query('ref_command');
            $paiement = Paiement_produit::where('reference', $transaction_id)->firstOrFail();
            $commande = $paiement->commande;

            $redirectUrl = self::SUCCESS_REDIRECT_URL . "?status=success&commande_id={$commande->id}&transaction_id={$transaction_id}";
            return redirect()->away($redirectUrl);
        } catch (Exception $e) {
            Log::error('Erreur lors du traitement du succès de paiement de produit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors du traitement du paiement.');
        }
    }

    /**
     * Gère l'annulation du paiement par l'utilisateur.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function gererAnnulationPaiement(Request $request)
    {
        Log::info('Annulation du paiement de produit par l\'utilisateur', ['request_data' => $request->all()]);
        return redirect()->route('home')->with('info', 'Votre paiement a été annulé.');
    }

    /**
     * Obtient le statut du paiement en fonction du type d'événement.
     *
     * @param  string  $type_event
     * @return string
     */
    private function obtenirStatutPaiement($type_event)
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

    /**
     * Mappe la méthode de paiement Paytech à votre format interne.
     *
     * @param  string  $paytech_method
     * @return string
     */
    private function mapperMethodePaiement($paytech_method)
    {
        $map = [
            'Orange Money' => 'orange_money',
            'Wave' => 'wave',
            'Free Money' => 'free',
            'Carte Bancaire' => 'carte',
            'PayPal' => 'paypal',
            'Joni Joni' => 'joni_joni',
            'Wari' => 'wari',
            'Poste Cash' => 'poste_cash',
        ];

        return $map[$paytech_method] ?? 'autre';
    }

    /**
     * Traite les actions nécessaires après un paiement réussi.
     *
     * @param  Commande  $commande
     * @return void
     */
    private function traiterPaiementReussi(Commande $commande)
    {
        Log::info('Traitement du paiement réussi', ['commande_id' => $commande->id]);

        // Mise à jour du stock des produits
        foreach ($commande->produits as $produit) {
            $produit->stock -= $produit->pivot->quantite;
            $produit->save();
        }

        // Exemple : envoi d'un email de confirmation
        // Mail::to($commande->user->email)->send(new ConfirmationCommande($commande));

        Log::info('Paiement de produit réussi traité', ['commande_id' => $commande->id]);
    }

    //afficher tous les paiements
    public function index()
    {
        // if (!auth()->check() || !auth()->user()->hasRole('admin')) {
        //     return response()->json(['message' => 'Accès refusé'], 403);
        // }

        $paiements = Paiement::with(['user', 'formation'])->get();

        return response()->json($paiements, 200);
    }
    //supprimer les paiements
    public function destroy($id)
    {
        // if (!auth()->check() || !auth()->user()->hasRole('admin')) {
        //     return response()->json(['message' => 'Accès refusé'], 403);
        // }

        $paiement = Paiement::find($id);

        if (!$paiement) {
            return response()->json(['message' => 'Paiement non trouvé'], 404);
        }

        try {
            $paiement->delete();
            return response()->json(['message' => 'Paiement supprimé avec succès'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors de la suppression du paiement', 'erreur' => $e->getMessage()], 500);
        }
    }
}
