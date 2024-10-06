<?php
namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PaytechService
{
    const URL = "https://paytech.sn";
    const PAYMENT_REQUEST_PATH = '/api/payment/request-payment';
    const PAYMENT_REDIRECT_PATH = '/payment/checkout/';
    const PAYMENT_SUCCESS_PATH = '/api/payment/success-payments';


    private $apiKey;
    private $apiSecret;
    private $query = [];
    private $customField = [];
    private $testMode = false;
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
            Log::error('Error fetching successful payments: ' . $e->getMessage());
            return null;
        }
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiSecret($apiSecret)
    {
        $this->apiSecret = $apiSecret;
    }

    public function send()
    {
        $params = [
            'item_name' => $this->query['item_name'] ?? '',
            'item_price' => $this->query['item_price'] ?? '',
            'command_name' => $this->query['command_name'] ?? '',
            'ref_command' => $this->refCommand,
            'env' =>  'test',
            'currency' => $this->currency,
            'ipn_url' => $this->notificationUrl['ipn_url'] ?? '',
            'success_url' => $this->notificationUrl['success_url'] ?? '',
            'cancel_url' => $this->notificationUrl['cancel_url'] ?? '',
            'custom_field' => json_encode($this->customField),
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
            } else if (isset($jsonResponse['error'])) {
                return [
                    'success' => -1,
                    'errors' => $jsonResponse['error']
                ];
            } else {
                return [
                    'success' => -1,
                    'errors' => [
                        'Internal Error: Unexpected response structure.'
                    ]
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => -1,
                'errors' => [
                    'Exception: ' . $e->getMessage()
                ]
            ];
        }
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    public function setCustomField($customField)
    {
        if (is_array($customField)) {
            $this->customField = $customField;
        }

        return $this;
    }

    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
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
