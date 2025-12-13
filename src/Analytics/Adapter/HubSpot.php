<?php

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class HubSpot extends Adapter
{
    /**
     * Endpoint for MixPanel Events
     */
    public string $endpoint = 'https://api.hubapi.com';

    public function __construct(string $token)
    {
        $this->headers = [
            'Authorization' => 'Bearer '.$token,
            'Content-Type' => '',
        ];
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function send(Event|array $event): bool
    {
        if (is_array($event)) {
            foreach ($event as $singleEvent) {
                $this->send($singleEvent);
            }

            return true;
        }
        if (! $this->enabled) {
            return false;
        }

        // HubSpot event tracking isn't possible due to their chrome based extention system
        return false;
    }

    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        // HubSpot event tracking isn't possible due to their chrome based extention system
        return false;
    }

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'HubSpot';
    }

    /**
     * Checks if a contact exists by the email ID. Returns the User ID if it exists and false if it doesn't.
     */
    public function contactExists(string $email): bool|int
    {
        try {
            $result = $this->call('GET', '/crm/v3/objects/contacts/'.urlencode($email).'?idProperty=email');
        } catch (\Throwable $e) {
            if ($e->getCode() == 404) {
                return false;
            }
            throw $e;
        }

        $result = json_decode($result, true);

        if ($result && $result['id']) {
            return $result['id'];
        } else {
            return false;
        }
    }

    /**
     * Create a contact
     */
    public function createContact(string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = ['properties' => [
            'email' => $email,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $phone,
        ]];

        $this->call('POST', '/crm/v3/objects/contacts', [
            'Content-Type' => 'application/json',
        ], $body);

        return true;
    }

    /**
     * Update contact
     */
    public function updateContact(string $contactId, string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = [
            'email' => $email,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $phone,
        ];

        try {
            $this->call('PATCH', '/crm/v3/objects/contacts/'.$contactId, [
                'Content-Type' => 'application/json',
            ], $body);

            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                // No changes to make
                return true;
            }

            throw $e;
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

        $this->call('DELETE', '/crm/v3/objects/contacts/'.$contact, [
            'Content-Type' => 'application/json',
        ]);

        return true;
    }

    /**
     * Account Exists
     */
    public function accountExists(string $name): bool|int
    {
        $result = $this->call('POST', '/crm/v3/objects/companies/search', [
            'Content-Type' => 'application/json',
        ], [
            'filterGroups' => [[
                'filters' => [
                    [
                        'value' => $name,
                        'propertyName' => 'name',
                        'operator' => 'EQ',
                    ],
                ],
            ]],
        ]);

        $result = json_decode($result, true);

        if ($result && $result['total'] > 0 && count($result['results']) > 0) {
            return $result['results'][0]['id'];
        } else {
            return false;
        }
    }

    /**
     * Create an account
     */
    public function createAccount(string $name, string $url = ''): bool
    {
        $body = ['properties' => [
            'name' => $name,
            'domain' => $url,
        ]];

        $this->call('POST', '/crm/v3/objects/companies', [
            'Content-Type' => 'application/json',
        ], $body);

        return true;
    }

    /**
     * Update an account
     */
    public function updateAccount(string $accountId, string $name, string $url = '', int $ownerId = 1, array $fields = []): bool
    {
        $body = [
            'name' => $name,
            'domain' => $url,
        ];

        try {
            $this->call('PATCH', '/crm/v3/objects/companies/'.$accountId, [
                'Content-Type' => 'application/json',
            ], $body);

            return true;
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                // No changes to make
                return true;
            }

            throw $e;
        }
    }

    /**
     * Delete an account
     */
    public function deleteAccount(string $accountId): bool
    {
        $this->call('DELETE', '/crm/v3/objects/companies/'.$accountId, [
            'Content-Type' => 'application/json',
        ]);

        return true;
    }

    /**
     * Sync an association
     *
     * Creates an association if it doesn't exist and updates it if it does
     */
    public function syncAssociation(string $accountId, string $contactId, string $role = ''): bool
    {
        // See if the association already exists

        $response = $this->call('GET', '/crm/v4/objects/contact/'.$accountId.'/associations/company');

        $response = json_decode($response, true);

        $associationId = null;

        foreach ($response['results'] as $association) {
            if ($association['from']['id'] == $contactId) {
                $associationId = $association['id'];
            }
        }

        if (empty($associationId)) {
            // Create the association
            $this->call('PUT', '/crm/v4/objects/contact/'.$contactId.'/associations/default/company/'.$accountId, [
                'Content-Type' => 'application/json',
            ]);
        } else {
            // Delete and recreate the association
            $this->call('DELETE', '/crm/v4/objects/contact/'.$contactId.'/associations/company/'.$accountId, [
                'Content-Type' => 'application/json',
            ]);

            $this->call('PUT', '/crm/v4/objects/contact/'.$contactId.'/associations/default/company/'.$accountId, [
                'Content-Type' => 'application/json',
            ]);
        }

        return true;
    }

    /**
     * Add a contact to a list
     */
    public function addToList(int $listId, int $contactId): bool
    {
        $this->call('PUT', '/crm/v3/lists/'.$listId.'/memberships/add', [
            'Content-Type' => 'application/json',
        ], [$contactId]);

        return true;
    }
}
