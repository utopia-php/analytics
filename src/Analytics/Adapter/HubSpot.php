<?php

namespace Utopia\Analytics\Adapter;

use Exception;
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
        ];
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function send(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // HubSpot event tracking isn't possible due to their chrome based extention system
        return true;
    }

    public function validate(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // HubSpot event tracking isn't possible due to their chrome based extention system
        return true;
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
            $result = $this->call('GET', '/crm/v3/objects/contacts/search', [], [
                'filterGroups' => [
                    'filters' => [
                        [
                            'value' => $email,
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                        ],
                    ],
                ],
            ]);

            $result = json_decode($result, true);

            if ($result && $result['total'] > 0) {
                return $result['results'][0]['id'];
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
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $phone,
        ]];

        try {
            $this->call('POST', '/crm/v3/objects/contacts', [
                'Content-Type' => 'application/json',
            ], $body);

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
            'firstname' => $firstName,
            'lastname' => $lastName,
            'phone' => $phone,
        ]];

        try {
            $this->call('PATCH', '/crm/v3/objects/contacts/'.$contactId, [
                'Content-Type' => 'application/json',
            ], $body);

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
            $this->call('DELETE', '/crm/v3/objects/contacts/'.$contact, [
                'Content-Type' => 'application/json',
            ]);

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
            $result = $this->call('GET', '/crm/v3/objects/companies/search', [], [
                'filterGroups' => [
                    'filters' => [
                        [
                            'value' => $name,
                            'propertyName' => 'name',
                            'operator' => 'EQ',
                        ],
                    ],
                ],
            ]);

            $result = json_decode($result, true);

            if ($result && $result['total'] > 0) {
                return $result['results'][0]['id'];
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
    public function createAccount(string $name, string $url = ''): bool
    {
        $body = [
            'name' => $name,
            'domain' => $url,
        ];

        try {
            $this->call('POST', '/crm/v3/objects/companies', [
                'Content-Type' => 'application/json',
            ], $body);

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
            $this->call('DELETE', '/crm/v3/objects/companies/'.$accountId, [
                'Content-Type' => 'application/json',
            ]);

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
                $body = [
                    'from' => [
                        'id' => $contactId,
                        'type' => 'CONTACT',
                    ],
                    'to' => [
                        'id' => $accountId,
                        'type' => 'COMPANY',
                    ],
                    'category' => 'HUBSPOT_DEFINED',
                    'definitionId' => 1,
                ];

                $this->call('PUT', '/crm/v4/objects/contact/'.$accountId.'/associations/company', [
                    'Content-Type' => 'application/json',
                ], $body);
            } else {
                // Update the association
                $body = [
                    'category' => 'HUBSPOT_DEFINED',
                    'definitionId' => 1,
                ];

                $this->call('PATCH', '/crm/v4/objects/contact/'.$accountId.'/associations/company/'.$associationId, [
                    'Content-Type' => 'application/json',
                ], $body);
            }
        } catch (\Exception $e) {
            $this->logError($e);

            return false;
        }
    }
}