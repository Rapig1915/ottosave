<?php

namespace App\Services\External;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use \Carbon\Carbon;
use App\Models\TapfiliateCustomer;

class TapfiliateService
{
    protected $client;
    protected $isEnabled = true;
    private $apiUri = 'https://api.tapfiliate.com';

    public function __construct()
    {
        $isTapfiliateConfigured = config('services.tapfiliate.api_key') && config('services.tapfiliate.api_key') !== 'insert_tapfiliate_api_key';
        if (!$isTapfiliateConfigured) {
            $this->isEnabled = false;
            throw new \Exception('Sorry, but it looks like your environment isn\'t configured with Tapfiliate credentials.');
        }
        $this->client = new Client([ 'base_uri' => $this->apiUri ]);
    }

    public function createCustomer($referralCode, $accountId)
    {
        $internalId = $this->getInternalCustomerId($accountId);
        $payload = [
            'headers' => $this->getRequestHeaders(),
            'json' => [
                'referral_code' => $referralCode,
                'customer_id' => $internalId,
                'status' => 'trial'
            ]
        ];
        $response = $this->client->post('/1.6/customers/', $payload);
        $customer = json_decode($response->getBody()->getContents());
        $tapfiliateCustomer = TapfiliateCustomer::create([
            'tapfiliate_id' => $customer->id,
            'customer_id' => $internalId,
            'account_id' => $accountId,
            'referral_code' => $referralCode
        ]);
        return $tapfiliateCustomer;
    }

    public function createConversionEvent($customerId, $amount, $conversionId, $commissionType = 'default')
    {
        $payload = [
            'headers' => $this->getRequestHeaders(),
            'json' => [
                'customer_id' => $customerId,
                'amount' => (float)$amount,
                'external_id' => $conversionId,
                'commission_type' => $commissionType,
            ]
        ];
        $response = $this->client->post('/1.6/conversions/', $payload);
    }

    public function getCustomerByInternalId($internalId)
    {
        $requestPath = "/1.6/customers/?customer_id=$internalId";
        $response = $this->client->get($requestPath, ['headers' => $this->getRequestHeaders()]);
        $results = json_decode($response->getBody()->getContents());
        $customer = $results[0] ?? null;
        return $customer;
    }

    public function deleteCustomer($tapfiliateId)
    {
        $requestPath = "/1.6/customers/$tapfiliateId/";
        $response = $this->client->delete($requestPath, ['headers' => $this->getRequestHeaders()]);
        return json_decode($response->getBody()->getContents());
    }

    public function deleteConversion($conversionId)
    {
        $requestPath = "/1.6/conversions/$conversionId/";
        $response = $this->client->delete($requestPath, ['headers' => $this->getRequestHeaders()]);
        return json_decode($response->getBody()->getContents());
    }

    public function getConversions()
    {
        $requestPath = "/1.6/conversions/";
        $response = $this->client->get($requestPath, ['headers' => $this->getRequestHeaders()]);
        return json_decode($response->getBody()->getContents());
    }

    private function getInternalCustomerId($accountId)
    {
        return config('services.tapfiliate.account_prefix') . '-ACCOUNT-' . $accountId;
    }

    private function getRequestHeaders()
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Api-Key' => config('services.tapfiliate.api_key'),
        ];
    }
}
