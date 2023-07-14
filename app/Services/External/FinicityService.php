<?php

namespace App\Services\External;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use \Carbon\Carbon;
use \App\Models\FinicityToken;
use \App\Models\Institution;
use \App\Models\FinicityCustomer;
use \App\Models\FinicityOauthInstitution;
use \App\Jobs\RefreshFinicityAuthToken;

class FinicityService
{
    public $base_uri = 'https://api.finicity.com';
    public $client;

    public $partner_id;
    public $app_key;
    public $secret;
    public $access_token;
    public $tokenRefreshAttempts = 0;

    public function __construct($shouldInitializeAccessToken = true)
    {
        $FinicityService = $this;
        if(config('finicity.partnerId') === 'insert_partner_id'){
            throw new \Exception('Sorry, but it looks like you need to setup Finicity with your API Keys.');
        };

        $FinicityService->partner_id = config('finicity.partnerId');
        $FinicityService->secret = config('finicity.secret');
        $FinicityService->app_key = config('finicity.appKey');

        $FinicityService->client = new Client([ 'base_uri' =>  $FinicityService->base_uri ]);
        if ($shouldInitializeAccessToken) {
            $FinicityService->initializeAccessToken();
        }
    }

    private function initializeAccessToken()
    {
        $FinicityService = $this;
        $currentAccessToken = FinicityToken::getCurrentToken();
        if ($currentAccessToken) {
            $FinicityService->access_token = $currentAccessToken->token;
        } else {
            if ($FinicityService->tokenRefreshAttempts < 3) {
                $FinicityService->tokenRefreshAttempts++;
                RefreshFinicityAuthToken::dispatchSync(); //refresh via job to funnel token creation attempts
                $FinicityService->initializeAccessToken();
            } else {
                throw new \Exception("Failed to refresh Finicity token", 500);
            }
        }
    }

    public function generateAccessToken()
    {
        $FinicityService = $this;

        $tokenPayload = [
            'json' => [
                'partnerId' => $FinicityService->partner_id,
                'partnerSecret' => $FinicityService->secret,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key
            ]
        ];

        $response = $FinicityService->client->post('aggregation/v2/partners/authentication', $tokenPayload);
        $result = json_decode($response->getBody()->getContents());
        $finicityToken = new FinicityToken();
        $finicityToken->token = $result->token;
        $finicityToken->save();

        return $finicityToken;
    }

    public function getConnectLink($user, $excludeRedirectLink)
    {
        $FinicityService = $this;

        $finicityCustomer = $user->current_account->finicity_customer;
        if (empty($finicityCustomer)) {
            $finicityCustomer = $FinicityService->createCustomer($user);
        }

        $customerId = $finicityCustomer->customer_id;
        $connectPayload = [
            'json' => [
                'partnerId' => $FinicityService->partner_id,
                'customerId' => $customerId,
                'type' => 'aggregation',
                'oauthOptions' => [
                    'enabled' => true,
                    'autoReplace' =>true
                ]
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];

        if (!$excludeRedirectLink) {
            $connectPayload['json']['redirectUri'] = request()->root() . '/api/v1/finicity/redirect-handler';
        }

        $response = $FinicityService->client->post('connect/v1/generate', $connectPayload);
        $link = json_decode($response->getBody()->getContents())->link;
        return $link;
    }

    public function getConnectFixLink($customerId, $institutionLoginId, $excludeRedirectLink)
    {
        $FinicityService = $this;

        $connectPayload = [
            'json' => [
                'partnerId' => $FinicityService->partner_id,
                'customerId' => $customerId,
                'institutionLoginId' => $institutionLoginId,
                'type' => 'fix',
                'oauthOptions' => [
                    'enabled' => true,
                ]
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];

        if (!$excludeRedirectLink) {
            $connectPayload['json']['redirectUri'] = request()->root() . '/api/v1/finicity/redirect-handler';
        }

        $response = $FinicityService->client->post('connect/v1/generate', $connectPayload);
        $link = json_decode($response->getBody()->getContents())->link;
        return $link;
    }

    public function getConnectV2Link($user, $excludeRedirectLink)
    {
        $FinicityService = $this;

        $finicityCustomer = $user->current_account->finicity_customer;
        if (empty($finicityCustomer)) {
            $finicityCustomer = $FinicityService->createCustomer($user);
        }

        $customerId = $finicityCustomer->customer_id;
        $connectPayload = [
            'json' => [
                'partnerId' => $FinicityService->partner_id,
                'customerId' => $customerId
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];

        $finicityExperienceId = config('finicity.experienceId');
        if(!empty($finicityExperienceId) && $finicityExperienceId !== 'insert_finicity_experience_id') {
            $connectPayload['json']['experience'] = $finicityExperienceId;
        }

        $oauthInstitutionIds = FinicityOauthInstitution::select('old_institution_id')->get();
        $hideBankIds = config('finicity.hideBankIds');
        if(!empty($hideBankIds) && $hideBankIds !== 'insert_comma_separated_bank_ids'){
            $hideBankIds = explode(",", $hideBankIds);
        } else {
            $hideBankIds = [];
        }

        if (count($oauthInstitutionIds) > 0 || count($hideBankIds) > 0) {
            $connectPayload['json']['institutionSettings'] = [];
            foreach ($oauthInstitutionIds as $oauthInstitution) {
                $connectPayload['json']['institutionSettings'][(string)$oauthInstitution->old_institution_id] = 'autoreplace';
            }
            foreach ($hideBankIds as $hideBankId) {
                $connectPayload['json']['institutionSettings'][(string)$hideBankId] = 'hidden';
            }
        }

        if (!$excludeRedirectLink) {
            $secureRoot = preg_replace('/http:\/\//', 'https://', request()->root(), 1);
            $redirectUri = $secureRoot . '/api/v1/finicity/redirect-handler';
            $connectPayload['json']['redirectUri'] = $redirectUri;
        }

        $connectPayload['body'] = json_encode($connectPayload['json']);
        unset($connectPayload['json']);

        $response = $FinicityService->client->post('connect/v2/generate', $connectPayload);
        $link = json_decode($response->getBody()->getContents())->link;
        return $link;
    }

    public function getConnectFixV2Link($customerId, $institutionLoginId, $excludeRedirectLink)
    {
        $FinicityService = $this;

        $connectPayload = [
            'json' => [
                'partnerId' => $FinicityService->partner_id,
                'customerId' => $customerId,
                'institutionLoginId' => $institutionLoginId,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];

        if (!$excludeRedirectLink) {
            $secureRoot = preg_replace('/http:\/\//', 'https://', request()->root(), 1);
            $redirectUri = $secureRoot . '/api/v1/finicity/redirect-handler';
            $connectPayload['json']['redirectUri'] = $redirectUri;
        }

        $response = $FinicityService->client->post('connect/v2/generate/fix', $connectPayload);
        $link = json_decode($response->getBody()->getContents())->link;
        return $link;
    }

    private function createCustomer($user)
    {
        $FinicityService = $this;
        $username = 'dym_' . config('finicity.env') . '_account_' . $user->current_account->id . '_' . uniqid();
        $customerPayload = [
            'json' => [
                'username' => $username,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        if (config('finicity.env') === 'production') {
            $customerCreationEndpoint = 'aggregation/v2/customers/active';
        } else {
            $customerCreationEndpoint = 'aggregation/v1/customers/testing';
        }

        $response = $FinicityService->client->post($customerCreationEndpoint, $customerPayload);
        $responseBody = json_decode($response->getBody()->getContents());
        $user->current_account->finicity_customer()->create([
            'customer_id' => $responseBody->id,
            'username' => $responseBody->username
        ]);
        $user->current_account->refresh();
        return $user->current_account->finicity_customer;
    }

    public function getCustomerAccounts($customerId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = 'aggregation/v1/customers/' . $customerId . '/accounts';
        $response = $FinicityService->client->get($requestPath, $payload);
        return json_decode($response->getBody()->getContents())->accounts;
    }

    public function refreshCustomerAccounts($customerId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Content-Length' => 0,
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = 'aggregation/v1/customers/' . $customerId . '/accounts';
        $response = $FinicityService->client->post($requestPath, $payload);
        return json_decode($response->getBody()->getContents())->accounts ?? [];
    }

    public function getCustomerAccountsByInstitution($customerId, $institutionId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = "/aggregation/v1/customers/{$customerId}/institutions/{$institutionId}/accounts";
        $response = $FinicityService->client->get($requestPath, $payload);
        return json_decode($response->getBody()->getContents())->accounts;
    }

    public function getInstitution($institutionId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = 'aggregation/v1/institutions/' . $institutionId;
        $response = $FinicityService->client->get($requestPath, $payload);
        return json_decode($response->getBody()->getContents());
    }

    public function getAccountTransactions(
        $customerId,
        $accountId,
        Carbon $startDate = null,
        Carbon $endDate = null,
        $offset = 1,
        $includePending = false
    ){
        $FinicityService = $this;
        $startDate = $startDate ? $startDate->timestamp : (new Carbon())->subWeeks(6)->timestamp;
        $endDate = $endDate ? $endDate->timestamp : (new Carbon())->timestamp;
        $limit = 1000;
        $payload = [
            'headers' => [
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = "/aggregation/v3/customers/{$customerId}/accounts/{$accountId}/transactions?fromDate={$startDate}&toDate={$endDate}&start={$offset}&limit={$limit}&includePending={$includePending}";
        $response = $FinicityService->client->get($requestPath, $payload);
        $responseBody = json_decode($response->getBody()->getContents());

        /**
         * Successful responses vs failed ones do not share the same
         * variables returned (i.e. code and/or status only on failures)
         * Success returns "found": 200
         */
        if (collect($responseBody)->has('transactions'))
        {
            return [
                'transactions' => $responseBody->transactions,
                'nextOffset' => !empty($responseBody->moreAvailable) && $responseBody->moreAvailable !== 'false' ? $offset + $limit : false,
                'response' => $responseBody
            ];
        } else {
            return [
                'transactions' => [],
                'nextOffset' => false,
                'response' => $responseBody
            ];
        }
        
    }

    public function deleteAccount($customerId, $accountId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = "/aggregation/v1/customers/{$customerId}/accounts/{$accountId}";
        try {
            $response = $FinicityService->client->delete($requestPath, $payload);
        } catch (RequestException $e) {
            $is404Error = $e->hasResponse() && $e->getResponse()->getStatusCode() === 404;
            if (!$is404Error) {
                throw $e;
            }
        }

        return true;
    }

    public function deleteCustomer($user, $customerId = null)
    {
        $FinicityService = $this;
        $finicityCustomer = $user ? $user->current_account->finicity_customer : false;

        if ($finicityCustomer && !$customerId) {
            $customerId = $finicityCustomer->customer_id;
        }

        if ($customerId) {
            $payload = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Finicity-App-Key' => $FinicityService->app_key,
                    'Finicity-App-Token' => $FinicityService->access_token
                ]
            ];
            $requestPath = 'aggregation/v1/customers/' . $customerId;
            $response = $FinicityService->client->delete($requestPath, $payload);
        }
    }

    public function deleteAllTestingCustomers()
    {
        $FinicityService = $this;
        $requestPath = 'aggregation/v1/customers?type=testing';
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $response = $FinicityService->client->get($requestPath, $payload);
        $customers = json_decode($response->getBody()->getContents())->customers;

        foreach ($customers as $customer) {
            $finictyCustomer = \App\Models\FinicityCustomer::where('customer_id', $customer->id)->first();
            if ($finictyCustomer) {
                $finictyCustomer->account->institutions()->where('type', '=', 'finicity')->delete();
                $finictyCustomer->delete();
            } else {
                $FinicityService->deleteCustomer(null, $customer->id);
            }
        }
    }

    public function addTransactionForTestingAccount(
        $customerId,
        $accountId,
        $amount = -50,
        $description = 'Test Finicity Transaction'
    ){
        $FinicityService = $this;
        $requestPath = "/aggregation/v1/customers/{$customerId}/accounts/{$accountId}/transactions";
        $payload = [
            'json' => [
                'amount' => $amount,
                'description' => $description,
                'postedDate' => Carbon::now()->timestamp,
                'transactionDate' => Carbon::now()->timestamp,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $response = $FinicityService->client->post($requestPath, $payload);
        return json_decode($response->getBody()->getContents());
    }

    public static function getErrorMessageFromAggregationStatusCode($statusCode)
    {
        switch ($statusCode) {
            case '103':
                $message = "The login credentials you entered are not valid for this account. Please log into your bank's site and make sure you have access and then try again.";
                break;
            case '108':
                $message = "User action is required at your financial institution. Log into your financial institution and check if you have any actions that need to be taken.";
                break;
            case '109':
                $message = "Your financial institution is requiring new security info. Try logging into your financial institution and changing or updating your security credentials.";
                break;
            case '169':
                $message = "Duplicate Account. In order to resolve one of the duplicate accounts must be deleted, and the accounts refreshed via the refresh button on the dashboard.";
                break;
            case '185':
                $message = "One of your security questions (e.g. name of your first pet) has expired and needs to be answered.";
                break;
            case '187':
                $message = "The answer to your security question was incorrect.";
                break;
            case '913':
                $message = "Account Has Been Closed";
                break;
            case '914':
                $message = "The account can no longer be located at the financial institution under the current set of credentials. If the account is available through your online banking portal contact support.";
                break;
            case '931':
                $message = "Bank security requires a one time passcode for every connection.";
                break;
            case '936':
                $message = "You have a language preference other than English at your financial institution, which is not supported. Please log in to your financial institution and change your language to English in order to connect.";
                break;
            case '102':
            case '320':
            case '580':
                $message = "Problem connecting. Please wait a few minutes and try again.";
                break;
            case '900':
                $message = "There is currently a problem with the connection to this financial institution and it's being worked on. Please try again later.";
                break;
            case '901':
            case '903':
            case '904':
                $message = "The connection to this financial institution is currently in the second phase of the creation process. Please try again in 2-3 days. Each attempt will store information needed by engineering to continue to fix the connection.";
                break;
            case '905':
            case '906':
            case '907':
                $message = "Institution down for maintenance";
                break;
            case '910':
                $message = "The connection is currently down and being worked on. Please check back in 3-5 days";
                break;
            case '915':
            case '916':
                $message = "Institution not working for a specific user or group of users.";
                break;
            case '920':
            case '921':
            case '922':
            case '923':
            case '924':
            case '925':
            case '926':
            case '927':
            case '928':
            case '929':
                $message = "This institution is not supported. Error code {$statusCode}";
                break;
            case '948':
                $message = "We have upgraded our connection to your institution, please re-enter your credentials.";
                break;
            case '945':
            case '946':
            case '947':
                $message = "Your institution has revoked access to this account, please re-enter your credentials or delete the account from Otto.";
                break;
            case '0':
            case null:
                $message = "";
                break;
            default:
                $message = 'Unknown status code';
                break;
        }
        return $message;
    }

    public function migrateInstitutionLoginIdAccounts($customerId, $institutionLoginId, $newInstitutionId)
    {
        $FinicityService = $this;
        $requestPath = "/aggregation/v1/customers/{$customerId}/institutionLogins/{$institutionLoginId}/institutions/{$newInstitutionId}";
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $response = $FinicityService->client->put($requestPath, $payload);
        return json_decode($response->getBody()->getContents());
    }

    public function deleteInstitutionLogin($customerId, $institutionLoginId)
    {
        $FinicityService = $this;
        $payload = [
            'headers' => [
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ]
        ];
        $requestPath = "/aggregation/v1/customers/{$customerId}/institutionLogins/{$institutionLoginId}";
        try {
            $response = $FinicityService->client->delete($requestPath, $payload);
        } catch (RequestException $e) {
            $is404Error = $e->hasResponse() && $e->getResponse()->getStatusCode() === 404;
            if (!$is404Error) {
                throw $e;
            }
        }

        return true;
    }
}
