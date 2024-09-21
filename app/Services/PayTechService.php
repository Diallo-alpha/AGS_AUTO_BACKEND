<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    const URL = "https://paytech.sn";
    // Chemins des différentes API utilisées
    const PAYMENT_REQUEST_PATH = '/api/payment/request-payment';
    const PAYMENT_REDIRECT_PATH = '/payment/checkout/';
    const PAYMENT_SUCCESS_PATH = '/api/payment/success-payments';

    private $apiKey;
    private $apiSecret;
    // Paramètres pour la requête de paiement
    private $query = [];
    private $currency = 'XOF';
    private $refCommand = '';
    private $notificationUrl = [];
    private $client;

    public function __construct()
    {
        // Chargement des clés API depuis le fichier de configuration
        $this->apiKey = config('services.paytech.api_key');
        $this->apiSecret = config('services.paytech.api_secret');
        $this->client = new Client();
    }

    public function initiatePayment($data)
    {
         // Préparation des paramètres pour la requête
        $params = [
            'item_name' => $data['item_name'] ?? '',
            'item_price' => $data['item_price'] ?? '',
            'ref_command' => $data['ref_command'] ?? '',
            'currency' => $data['currency'] ?? 'XOF',
            'ipn_url' => $data['callback_url'] ?? route('paytech.ipn'),
            'success_url' => $data['success_url'] ?? '',
            'cancel_url' => $data['cancel_url'] ?? '',
            'env' => 'prod',
        ];

        try {
            // Envoi de la requête POST à l'API PayTech
            $response = $this->client->post(self::URL . self::PAYMENT_REQUEST_PATH, [
                'form_params' => $params,
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8'
                ]
            ]);

            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            Log::info('Réponse PayTech', ['response' => $jsonResponse]);

            if (isset($jsonResponse['token'])) {
                return [
                    'success' => true,
                    'token' => $jsonResponse['token'],
                    'redirect_url' => self::URL . self::PAYMENT_REDIRECT_PATH . $jsonResponse['token']
                ];
            } else {
                return [
                    'success' => false,
                    'errors' => $jsonResponse['error'] ?? 'Erreur interne'
                ];
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Erreur PayTech', [
                'message' => $e->getMessage(),
                'request' => $e->getRequest(),
                'response' => $e->hasResponse() ? $e->getResponse() : null,
            ]);
            return [
                'success' => false,
                'errors' => 'Erreur de communication avec PayTech'
            ];
        }
    }

     // Méthode pour récupérer les paiements réussis
    public function getSuccessfulPayments()
    {
        try {
            $response = $this->client->get(self::URL . self::PAYMENT_SUCCESS_PATH, [
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des paiements réussis: ' . $e->getMessage());
            return null;
        }
    }

    // Méthodes pour définir les attributs de la classe
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function setCurrency($currency)
    {
        $this->currency = strtolower($currency);
        return $this;
    }

    public function setRefCommand($refCommand)
    {
        $this->refCommand = $refCommand;
        return $this;
    }

    public function setNotificationUrl($notificationUrl)
    {
        $this->notificationUrl = $notificationUrl;
        return $this;
    }
}
