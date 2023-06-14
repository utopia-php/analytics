<?php

// Note: ActiveCampaign requires the email prop to be set.
// It also won't create contacts, it'll only add events to pre-existing contacts.

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;
use Sahils\UtopiaFetch\Client;

class ActiveCampaign extends Adapter
{
    /**
     *  Endpoint for ActiveCampaign
     */
    public string $endpoint;

    /**
     * Event Key for ActiveCampaign
     */
    private string $key;

    /**
     * ActiveCampaign actid.
     */
    private string $actId;

    /**
     * ActiveCampaign apiKey
     */
    private string $apiKey;

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'Active Campaign';
    }

    /**
     * Checks if a contact exists by the email ID. Returns the User ID if it exists and false if it doesn't.
     */
    public function contactExists(string $email): bool|int
    {
        try {
            $result = Client::fetch(
                url: $this->endpoint.'/api/3/contacts',
                method: 'GET',
                query: [
                    'email' => $email,
                ],
            )->getBody();

            $result = json_decode($result, true);

            if ($result && $result['meta']['total'] > 0) {
                return $result['contacts'][0]['id'];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Create a contact
     */
    public function createContact(string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = ['contact' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone,
        ]];

        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/contacts',
                method: 'POST',
                body: $body,
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Update contact
     */
    public function updateContact(string $contactId, string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = ['contact' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone,
        ]];

        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/contacts'.$contactId,
                method: 'PUT',
                body: $body,
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Delete a contact
     */
    public function deleteContact(string $email): bool
    {
        $contact = $this->contactExists($email);

        if (! $contact) {
            return false;
        }

        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/contacts'.$contact,
                method: 'DELETE'
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Account Exists
     */
    public function accountExists(string $name): bool|int
    {
        try {
            $result = Client::fetch(
                url: $this->endpoint.'/api/3/accounts',
                method: 'GET',
                query: [
                    'search' => $name
                ]
            )->getBody();

            if (intval(json_decode($result, true)['meta']['total']) > 0) {
                return intval((json_decode($result, true))['accounts'][0]['id']);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Create an account
     */
    public function createAccount(string $name, string $url = '', int $ownerId = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerId,
            'fields' => array_values(array_filter($fields, function ($value) {
                return $value['fieldValue'] !== '' && $value['fieldValue'] !== null && $value['fieldValue'] !== false;
            })),
        ]];

        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/accounts',
                method: 'POST',
                body: $body,
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Update an account
     */
    public function updateAccount(string $accountId, string $name, string $url = '', int $ownerId = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerId,
            'fields' => array_values(array_filter($fields, function ($value) {
                return $value['fieldValue'] !== '' && $value['fieldValue'] !== null && $value['fieldValue'] !== false;
            })),
        ]];

        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/accounts/'.$accountId,
                method: 'PUT',
                body: array_filter($body),
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Delete an account
     */
    public function deleteAccount(string $accountId): bool
    {
        try {
            Client::fetch(
                url: $this->endpoint.'/api/3/accounts/'.$accountId,
                method: 'DELETE'
            );

            return true;
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }

    /**
     * Sync an association
     *
     * Creates an association if it doesn't exist and updates it if it does
     */
    public function syncAssociation(string $accountId, string $contactId, string $role = ''): bool
    {
        // See if the association already exists

        try {
            $result = Client::fetch(
                url: $this->endpoint.'/api/3/accountContacts',
                method: 'GET',
                query: [
                    'filters[account]' => $accountId,
                    'filters[contact]' => $contactId,
                ]
            )->getBody();
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }

        if (intval(json_decode($result, true)['meta']['total']) > 0) {
            // Update the association
            $associationId = intval((json_decode($result, true))['accountContacts'][0]['id']);

            try {
                $result = Client::fetch(
                    url: $this->endpoint.'/api/3/accountContacts/'.$associationId,
                    method: 'PUT',
                    body: [
                        'accountContact' => [
                            'jobTitle' => $role,
                        ],
                    ]
                )->getBody();

                return true;
            } catch (\Exception $e) {
                $this->logError($e);

                return false;
            }
        } else {
            // Create the association
            $result = Client::fetch(
                url: $this->endpoint.'/api/3/accountContacts',
                method: 'POST',
                body: [
                    'accountContact' => [
                        'account' => $accountId,
                        'contact' => $contactId,
                        'jobTitle' => $role,
                    ],
                ]
            )->getBody();

            return true;
        }
    }

    /**
     * @return ActiveCampaign
     */
    public function __construct(string $key, string $actId, string $apiKey, string $organisationId)
    {
        $this->key = $key;
        $this->actId = $actId;
        $this->apiKey = $apiKey;
        $this->endpoint = 'https://'.$organisationId.'.api-us1.com/'; // ActiveCampaign API URL, Refer to https://developers.activecampaign.com/reference/url for more details.
        $this->headers = [
            'Api-Token' => $this->apiKey,
            'Content-Type' => null,
        ];
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function send(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $query = [
            'key' => $this->key,
            'event' => $event->getName(),
            'actid' => $this->actId,
            'eventdata' => json_encode($event->getProps()),
            'visit' => json_encode(['email' => $event->getProp('email')]),
        ];

        $query = array_filter($query, fn ($value) => ! is_null($value) && $value !== '');
        $res = Client::fetch(
            url: 'https://trackcmp.net/event',
            method: 'POST',
            body: $query
        )->getBody();
        if (json_decode($res, true)['success'] === 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Sets the client IP address.
     *
     * @param  string  $ip The IP address to use.
     */
    public function setClientIP(string $clientIP): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Sets the client user agent.
     *
     * @param  string  $userAgent The user agent to use.
     */
    public function setUserAgent(string $userAgent): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set Tags
     */
    public function setTags(string $contactId, array $tags): bool
    {
        foreach ($tags as $tag) {
            try {
                Client::fetch(
                    url: $this->endpoint.'/api/3/contactTags',
                    method: 'POST',
                    body: [
                        'contactTag' => [
                            'contact' => $contactId,
                            'tag' => $tag,
                        ],
                    ]
                );
            } catch (\Exception $e) {
                $this->logError($e);

                return false;
            }
        }

        return true;
    }

    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $email = $event->getProp('email');

        if (empty($email)) {
            throw new \Exception('Email is required.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email address.');
        }

        $contactID = $this->contactExists($email);
        $foundLog = false;

        // Get contact again, since AC doesn't refresh logs immediately
        $response = Client::fetch(
            url: $this->endpoint.'/api/3/activities',
            method: 'GET',
            query: [
                'contact' => $contactID,
                'orders[tstamp]' => 'DESC',
            ]
        )->getBody();

        $response = json_decode($response, true);

        if (empty($response['trackingLogs'])) {
            throw new \Exception('Failed to find event on ActiveCampaign side.');
        }

        foreach ($response['trackingLogs'] as $log) {
            if ($log['type'] === $event->getName()) {
                $foundLog = true;
                break;
            }
        }

        if (! $foundLog) {
            throw new \Exception('Failed to find event on ActiveCampaign side.');
        }

        return true;
    }
}
