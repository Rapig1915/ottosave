<?php

namespace App\Services\External;

use Illuminate\Http\Request;
use Braintree_Gateway;

class BraintreeService
{
    protected $gateway;

    public function __construct(){
        $BraintreeService = $this;

        $isBraintreeConfigured = config('services.braintree.merchant_id') && config('services.braintree.merchant_id') !== 'insert_merchant_id';

        if(!$isBraintreeConfigured){
            throw new \Exception('Sorry, but it looks like your .env file isn\'t setup with Braintree API Keys.');
        };

        $BraintreeService->gateway = new Braintree_Gateway([
            'environment' => config('services.braintree.env'),
            'merchantId' => config('services.braintree.merchant_id'),
            'publicKey' => config('services.braintree.public_key'),
            'privateKey' => config('services.braintree.private_key')
       ]);
    }

    public function getClientToken($braintreeCustomerId = null)
    {
        $BraintreeService = $this;
        try {
            $options = [];
            if ($braintreeCustomerId) {
                $options['customerId'] = $braintreeCustomerId;
            }
            $token = $BraintreeService->gateway->clientToken()->generate($options);
            return $token;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    public function createSale($amount, $paymentIdentifierType, $paymentIdentifier, $submitForSettlement = true)
    {
        $BraintreeService = $this;
        $transactionDetails = [
            'amount' => $amount,
            'options' => [
                'submitForSettlement' => $submitForSettlement,
            ]
        ];

        switch ($paymentIdentifierType) {
            case 'nonce':
                $transactionDetails['paymentMethodNonce'] = $paymentIdentifier;
                break;
            case 'token':
                $transactionDetails['paymentMethodToken'] = $paymentIdentifier;
                break;
            case 'customerId':
                $transactionDetails['customerId'] = $paymentIdentifier;
                break;
            default:
                throw new Exception("You must specify the type of payment");
        }

        $transactionResult = $BraintreeService->gateway->transaction()->sale($transactionDetails);
        if ($transactionResult->success) {
            return $transactionResult;
        } else {
            throw new \Exception($transactionResult->message);
        }
    }

    public function addCustomerFromPaymentNonce($paymentNonce)
    {
        $BraintreeService = $this;
        $customerPayload = [
            'paymentMethodNonce' => $paymentNonce
        ];
        $result = $BraintreeService->gateway->customer()->create($customerPayload);

        if ($result->success) {
            return $result->customer;
        } else {
            throw new \Exception($result->message);
        }
    }

    public function createPaymentMethod($customerId, $paymentNonce)
    {
        $BraintreeService = $this;
        $payload = [
            'customerId' => $customerId,
            'paymentMethodNonce' => $paymentNonce,
            'options' => [
              'makeDefault' => true,
              'verifyCard' => true
            ]
        ];
        $result = $BraintreeService->gateway->paymentMethod()->create($payload);
        if ($result->success) {
            return $result->paymentMethod;
        } else {
            throw new \Exception($result->message);
        }
    }

    public function submitTransactionForSettlement($transactionId)
    {
        $BraintreeService = $this;
        $result = $BraintreeService->gateway->transaction()->submitForSettlement($transactionId);

        if ($result->success) {
            return $result->transaction;
        } else {
            throw new \Exception($result->message);
        }
    }

    public function getTransaction($transactionId)
    {
        $BraintreeService = $this;
        $transaction = $BraintreeService->gateway->transaction()->find($transactionId);
        return $transaction;
    }
}
