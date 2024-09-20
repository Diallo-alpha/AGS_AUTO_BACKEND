<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PayTechService
{
    const URL = "https://paytech.sn";
    const PAYMENT_REQUEST_PATH = '/api/payment/request-payment';
    const PAYMENT_REDIRECT_PATH = '/payment/checkout/';
    const PAYMENT_SUCCESS_PATH = '/api/payment/success-payments'; // Utilisé pour récupérer les paiements réussis

    private $apiKey;
    private $apiSecret;
    private $query = [];
    private $currency = 'XOF';
    private $refCommand = '';
    private $notificationUrl = [];
    private $client;

    public function __construct($apiKey, $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->client = new Client();
    }

    // Méthode pour initier un paiement
    public function initiatePayment($data)
    {
        $params = [
            'item_name' => $data['item_name'] ?? '',
            'item_price' => $data['item_price'] ?? '',
            'ref_command' => $data['ref_command'] ?? '',
            'currency' => $data['currency'] ?? 'XOF',
            'ipn_url' => $data['callback_url'] ?? '',
            'success_url' => $data['success_url'] ?? '',
            'cancel_url' => $data['cancel_url'] ?? '',
            'env' => 'prod',
        ];

        try {
            $response = $this->client->post(self::URL . self::PAYMENT_REQUEST_PATH, [
                'form_params' => $params,
                'headers' => [
                    "API_KEY" => $this->apiKey,
                    "API_SECRET" => $this->apiSecret,
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=utf-8'
                ]
            ]);

            $jsonResponse = json_decode($response->getBody()->getContents(), true);

            if (isset($jsonResponse['token'])) {
                return [
                    'success' => 1,
                    'token' => $jsonResponse['token'],
                    'redirect_url' => self::URL . self::PAYMENT_REDIRECT_PATH . $jsonResponse['token']
                ];
            } else {
                return [
                    'success' => -1,
                    'errors' => $jsonResponse['error'] ?? 'Erreur interne'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => -1,
                'errors' => ['Exception: ' . $e->getMessage()]
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

    // Autres setters et méthodes...

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

