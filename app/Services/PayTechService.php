<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    const URL = "https://paytech.sn";
    const PAYMENT_REQUEST_PATH = '/api/payment/request-payment';
    const PAYMENT_REDIRECT_PATH = '/payment/checkout/';
    const PAYMENT_SUCCESS_PATH = '/api/payment/success-payments';

    private $apiKey;
    private $apiSecret;
    private $client;

    public function __construct()
    {
        $this->apiKey = config('services.paytech.api_key');
        $this->apiSecret = config('services.paytech.api_secret');
        $this->client = new Client();

        Log::info('Clés API initialisées pour PayTech');
    }

    /**
     * Initie un paiement via PayTech
     */
    public function initiatePayment(array $data): array
    {
        Log::info('Initialisation du paiement avec les données', ['data' => $data]);

        $params = [
            'item_name' => $data['description'] ?? 'Paiement',
            'item_price' => max(100, intval($data['montant'])),
            'currency' => $data['currency'] ?? 'XOF',
            'ref_command' => 'REF-' . time(),
            'ipn_url' => $data['ipn_url'] ?? config('services.paytech.ipn_url'),
            'success_url' => $data['success_url'] ?? route('paiements.success'),
            'cancel_url' => $data['cancel_url'] ?? route('payment.cancel'),
            'env' => 'prod',
        ];

        Log::info('Paramètres préparés pour PayTech', ['params' => $params]);

        try {
            $response = $this->client->post(self::URL . self::PAYMENT_REQUEST_PATH, [
                'form_params' => $params,
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                ]
            ]);

            $jsonResponse = json_decode($response->getBody()->getContents(), true);
            Log::info('Réponse reçue de PayTech', ['response' => $jsonResponse]);

            if (isset($jsonResponse['token'])) {
                return [
                    'success' => true,
                    'token' => $jsonResponse['token'],
                    'redirect_url' => self::URL . self::PAYMENT_REDIRECT_PATH . $jsonResponse['token']
                ];
            }

            return ['success' => false, 'errors' => $jsonResponse['error'] ?? 'Erreur inconnue'];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la requête PayTech', ['message' => $e->getMessage()]);
            return ['success' => false, 'errors' => 'Erreur de communication avec PayTech'];
        }
    }

    /**
     * Récupère les paiements réussis
     */
    public function getSuccessfulPayments(): ?array
    {
        try {
            $response = $this->client->get(self::URL . self::PAYMENT_SUCCESS_PATH, [
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des paiements', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Test des clés API PayTech
     */
    public function testApiKeys(): bool
    {
        $params = [
            'item_name' => 'Test Payment',
            'item_price' => 100,
            'currency' => 'XOF',
            'ref_command' => 'REF-' . time(),
            'ipn_url' => config('services.paytech.ipn_url'),
            'success_url' => route('paiements.success'),
            'cancel_url' => route('payment.cancel'),
            'env' => 'prod',
        ];

        try {
            $response = $this->client->post(self::URL . self::PAYMENT_REQUEST_PATH, [
                'form_params' => $params,
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                ]
            ]);

            Log::info('Test des clés API réussi', ['status_code' => $response->getStatusCode()]);
            return $response->getStatusCode() === 200;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Erreur lors du test des clés API', [
                'message' => $e->getMessage(),
                'request' => $e->getRequest()->getUri(),
                'response' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 'Aucune réponse',
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Erreur inattendue lors du test des clés API', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
