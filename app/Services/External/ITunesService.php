<?php

namespace App\Services\External;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use \Carbon\Carbon;

class ITunesService
{
    protected $client;

    private $password;
    private $verificationAttemptCount;
    private $productionUri = 'https://buy.itunes.apple.com';
    private $sandboxUri = 'https://sandbox.itunes.apple.com';

    public function __construct()
    {
        $ITunesService = $this;

        $isITunesConfigured = config('services.itunes.shared_secret') && config('services.itunes.shared_secret') !== 'insert_itunes_secret';
        if (!$isITunesConfigured) {
            throw new \Exception('Sorry, but it looks like your environment isn\'t configured with ITunes credentials.');
        }

        $ITunesService->password = config('services.itunes.shared_secret');
        if (config('services.itunes.env') === 'sandbox') {
            $ITunesService->client = new Client([ 'base_uri' => $ITunesService->sandboxUri ]);
        } else {
            $ITunesService->client = new Client([ 'base_uri' => $ITunesService->productionUri ]);
        }
        $ITunesService->verificationAttemptCount = 0;
    }

    public function verifyReceipt($encodedReceipt)
    {
        $ITunesService = $this;
        $receiptVerificationPayload = [
            'json' => [
                'receipt-data' => $encodedReceipt,
                'password' => $ITunesService->password
            ],
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        $ITunesService->verificationAttemptCount++;
        $response = $ITunesService->client->post('verifyReceipt', $receiptVerificationPayload);
        $responseBody = json_decode($response->getBody()->getContents());

        $receiptVerified = $responseBody->status == 0 || $responseBody->status == 21006; // 21006 indicates a valid receipt for expired subscription
        $encounteredITunesServerError = $responseBody->status >= 21100 && $responseBody->status <= 21199;
        $testReceiptSentToProductionServer = $responseBody->status == 21007;

        if ($receiptVerified) {
            return $responseBody;
        } else if ($encounteredITunesServerError) {
            $retryVerification = $responseBody->{'is-retryable'} && $ITunesService->verificationAttemptCount < 5;
            if ($retryVerification) {
                return $ITunesService->verifyReceipt($encodedReceipt);
            } else {
                throw new \Exception("ITunes server encountered an internal server error.");
            }
        } else if ($testReceiptSentToProductionServer) {
            $retryVerification = $ITunesService->verificationAttemptCount < 2;
            if ($retryVerification) {
                $ITunesService->client = new Client([ 'base_uri' => $ITunesService->sandboxUri ]);
                return $ITunesService->verifyReceipt($encodedReceipt);
            } else {
                throw new \Exception("Unable to process test receipt in production environment.");
            }
        } else {
            $errorMessage = $ITunesService->getReceiptErrorMessage($responseBody->status);
            throw new \Exception($errorMessage);
        }

    }

    private function getReceiptErrorMessage($statusCode)
    {
        $errorMessage = '';
        switch ($statusCode) {
            case '21000':
                $errorMessage = "Receipt verification payload was unreadable by iTunes server";
                break;
            case '21002':
                $errorMessage = "The data in the receipt-data property was malformed or missing.";
                break;
            case '21003':
                $errorMessage = "The receipt could not be authenticated.";
                break;
            case '21004':
                $errorMessage = "The shared secret you provided does not match the shared secret on file for your account.";
                break;
            case '21005':
                $errorMessage = "The receipt server is not currently available.";
                break;
            case '21007':
                $errorMessage = "This receipt is from the test environment, but it was sent to the production environment for verification.";
                break;
            case '21008':
                $errorMessage = "This receipt is from the production environment, but it was sent to the test environment for verification.";
                break;
            case '21010':
                $errorMessage = "This receipt could not be authorized.";
                break;
            default:
                $errorMessage = "Internal server error";
                break;
        }

        return $errorMessage;
    }
}
