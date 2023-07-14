<?php

namespace App\Services\External;

use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ActiveCampaignService
{
    private $listId;
    private $base_uri;

    /**
     * Construct
     */
    public function __construct()
    {
        $ActiveCampaignService = $this;

        $isApiKeySet = config('services.active_campaign.key') && config('services.active_campaign.key') !== 'insert_active_campaign_api_key';
        $isListIdSet = config('services.active_campaign.list_id') && config('services.active_campaign.list_id') !== 'insert_active_campaign_list_id';
        $isUrlSet = config('services.active_campaign.url') && config('services.active_campaign.url') !== 'insert_active_campaign_url';
        $isActiveCampaignConfigured = $isApiKeySet && $isListIdSet && $isUrlSet;
        if (!$isActiveCampaignConfigured) {
            throw new \Exception('Sorry, but it looks like your environment isn\'t configured to work with Active Campaign credentials.');
        }

        $ActiveCampaignService->listId = config('services.active_campaign.list_id');
        $ActiveCampaignService->apiKey = config('services.active_campaign.key');
        $ActiveCampaignService->base_uri = config('services.active_campaign.url');

        $this->defaultStatus = 1; // 1 - Subscribed, 2 - Unsubscribed
    }

    /**
     * Retrieve Contat via email from Active Campaign
     *
     * @param email $email
     * @return var mixed contact
     */
    public function getContactByEmail($email)
    {
        $ActiveCampaignService = $this;
        $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
        $encodedEmail = urlencode($email);
        $endpoint = "/api/3/contacts?email={$encodedEmail}";
        $requestPayload = [
            'headers' => [
                'api-token' => $ActiveCampaignService->apiKey
            ]
        ];

        $response = $client->get($endpoint, $requestPayload);
        $contacts = collect(json_decode($response->getBody()->getContents())->contacts);
        if ($contacts->count() == 0) {
            return false;
        }
        return $contacts->first();
    }

    /**
     * Sync user to Active Campaign
     *
     * @param User $user
     * @param integer $status [-1 => Any, 0 => Unconfirmed, 1 => Active, 2 => Unsubscribed, 3 => Bounced]
     * @return mixed $response
     */
    public function syncUserToActiveCampaign(User $user, $status = 1)
    {
        $ActiveCampaignService = $this;
        $contactDetails = $ActiveCampaignService->getContactDetailsForUser($user);
        try {
            $contactByEmail = $ActiveCampaignService->getContactByEmail($user->last_verified_email);
        } catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                $contactByEmail = false;
            } else {
                throw $e;
            }
        }
        if ($contactByEmail) {
            // dd($contactByEmail->id);
            $activeCampaignContact = $ActiveCampaignService->getContact($contactByEmail->id);
            if ($status === 'unsubscribed' || $status === 2) {
                /**
                 * TODO: Need to determine how to unsubscribe contact from list and specify which list
                 * There is alerady an enpoint that requires the list to be identified and is a simple request
                 * Can likely just call that action to unsubscribe here
                 */
                // $ActiveCampaignService->syncToList($contactByEmail->id, $ActiveCampaignService->listId, 2);
                // $contactDetails['status'] = 2;
                // [-1 => Any, 0 => Unconfirmed, 1 => Active, 2 => Unsubscribed, 3 => Bounced]
                // $ActiveCampaignService->unsubscribeUserFromList($id, $ActiveCampaignService->listId;
            }
            if ($activeCampaignContact) {
                $id = (int) $activeCampaignContact['id'];
                return $ActiveCampaignService->updateActiveCampaignContact($id, $contactDetails, $user->last_verified_email);
            }
        } else {
            return $ActiveCampaignService->addActiveCampaignContact($contactDetails);
        }
    }

    /**
     * Retrieve Contact via ID from Active Campaign
     *
     * @param integer $id
     * @return mixed $response
     */
    public function getContact(int $id)
    {
        $ActiveCampaignService = $this;
        $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
        $endpoint = "/api/3/contacts/{$id}";
        $requestPayload = [
            'headers' => [
                'api-token' => $ActiveCampaignService->apiKey
            ]
        ];

        $response = $client->get($endpoint, $requestPayload);
        $contact = collect(json_decode($response->getBody()->getContents())->contact);
        if ($contact->count() == 0) {
            abort(400, 'Unable to find Active Campaign user by email address');
        }
        return $contact;
    }

    /**
     * Generate array of contact details to pass to Active Campaign
     *
     * @param User $user
     * @return mixed $contactDetails
     */
    public function getContactDetailsForUser(User $user)
    {
        if (!$user->last_verified_email) {
            abort(400, 'Cannot sync user with Active Campaign, user has not yet verified their email address.');
        }

        $contactDetails = [
            'contact' => [
                'email' => $user->last_verified_email,
                'first_name' => (string)$user->first_name ?? '',
                'last_name' =>  (string)$user->last_name ?? '',
                'fieldValues' => [
                    ['field' => '1', 'value' => (string)$user->id ?? ''],
                    ['field' => '2', 'value' => (string)$user->current_account->subscription_plan ?? ''],
                    ['field' => '3', 'value' => (string)$user->current_account->subscription_origin ?? ''],
                    ['field' => '4', 'value' => (string)$user->current_account->status ?? ''],
                    ['field' => '5', 'value' => (string)count($user->current_account->institutionAccounts) ?? ''],
                    ['field' => '7', 'value' => (string)$user->created_at ?? ''],
                    ['field' => '8', 'value' => implode(',', $user->current_account->institutions->pluck('name')->toArray())]
                ]
            ]
        ];

        return $contactDetails;
    }

    /**
     * Generate array of list details to pass to Active Campaign
     *
     * @param int $id Active Campaign contact ID
     * @param int $listId Active Campaign list ID
     * [-1 => Any, 0 => Unconfirmed, 1 => Active, 2 => Unsubscribed, 3 => Bounced]
     * @param int $statusID
     * @return mixed $contactDetails
     */
    public function getContactListBody(int $id, int $listId, int $statusId)
    {
        if (!$id || !$listId) {
            throw new \Exception("Cannot add user to a list without user ID and list ID.");
        }

        $contactListDetails = [
            'contactList' => [
                'list' => $listId,
                'contact' => $id,
                'status' =>  $statusId ? $statusId : 1
            ]
        ];

        return $contactListDetails;
    }

    /**
     * Add contact to Active Campaign
     *
     * @param array $contactDetails
     * @return mixed $response
     */
    private function addActiveCampaignContact($contactDetails)
    {
        $ActiveCampaignService = $this;
        if (!$contactDetails['contact']['email']) {
            throw new \Exception("Cannot add Active Campaign contact, missing value for email.");
        }
        $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
        $endpoint = "/api/3/contacts";
        $requestPayload = [
            'headers' => [
                'api-token' => $ActiveCampaignService->apiKey
            ],
            'body' => json_encode($contactDetails)
        ];

        $response = $client->post($endpoint, $requestPayload);
        $contact = collect(json_decode($response->getBody()->getContents())->contact);

        /**
         * Add new user to Trial list
         */
        $this->syncToList($contact['id'], $ActiveCampaignService->listId, $this->defaultStatus);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Sync Contact's subscription to list
     * This will allow for unsubscribes as well
     *
     * @param int $id Contact ID from Active Campaign
     * @param int $listId List ID from Active Campaign
     * @param int $statusId Status ID
     * @return mixed
     */
    public function syncToList($id, $listId, $statusId)
    {
        $ActiveCampaignService = $this;
        if (!$id || !$listId) {
            throw new \Exception("Cannot add user to a list without user ID and list ID.");
        }
        $contactListDetails = $ActiveCampaignService->getContactListBody($id, $listId, $statusId);
        // dd($contactListDetails);
        $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
        $endpoint = "/api/3/contactLists";
        $requestPayload = [
            'headers' => [
                'api-token' => $ActiveCampaignService->apiKey
            ],
            'body' => json_encode($contactListDetails)
        ];
        $response = $client->post($endpoint, $requestPayload);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Update contact details
     *
     * @param int $id
     * @param array $contactDetails
     * @param email $currentemail
     * @return mixed $response
     */
    public function updateActiveCampaignContact($id, $contactDetails, $currentemail)
    {
        $ActiveCampaignService = $this;
        if (!$currentemail) {
            throw new \Exception("Cannot update Active Campaign contact, missing value for current email.");
        }
        $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
        $endpoint = "/api/3/contacts/{$id}";
        $requestPayload = [
            'headers' => [
                'api-token' => $ActiveCampaignService->apiKey
            ],
            'body' => json_encode($contactDetails)
        ];
        $response = $client->put($endpoint, $requestPayload);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Update contact email address
     *
     * @param email $currentemail
     * @param email $newEmail
     * @return mixed $response
     */
    public function updateContactEmail($currentemail, $newEmail)
    {
        $ActiveCampaignService = $this;
        if (!$currentemail) {
            throw new \Exception("Cannot update Active Campaign contact, missing value for current email.");
            
        }
        $activeCampaignContact = $ActiveCampaignService->getContactByEmail($currentemail);
        $id = (int) $activeCampaignContact->id;
        if ($id) {
            $client = new Client(['base_uri' => $ActiveCampaignService->base_uri]);
            $endpoint = "/api/3/contacts/{$id}";
            $contact = [
                'contact' => [
                    'email' => $newEmail
                ]
            ];
            $requestPayload = [
                'headers' => [
                    'api-token' => $ActiveCampaignService->apiKey
                ],
                'body' => json_encode($contact)
            ];
            $response = $client->put($endpoint, $requestPayload);

            return json_decode($response->getBody()->getcontents());
        } else {
            return false;
        }
    }
}