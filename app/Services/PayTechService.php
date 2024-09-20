<?php 
namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class PayTechService
{
    protected $client;
    protected $apiKey;
    protected $apiSecret;
    protected $apiUrl;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('PAYTECH_API_KEY');
        $this->apiSecret = env('PAYTECH_API_SECRET');
        $this->apiUrl = env('PAYTECH_API_URL');
    }

    public function initiatePayment($data)
    {
        try {
            $response = $this->client->post($this->apiUrl . '/payment/initiate', [
                'headers' => [
                    'API_KEY' => $this->apiKey,
                    'API_SECRET' => $this->apiSecret,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            // Gérer les erreurs de requête
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
