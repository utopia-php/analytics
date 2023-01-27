<?php

// Note: ActiveCampaign requires the email prop to be set.
// It also won't create contacts, it'll only add events to pre-existing contacts.

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class ActiveCampaign extends Adapter
{
    /**
     *  Endpoint for ActiveCampaign
     *  @var string
     */
    public string $endpoint;

    /**
     * Event Key for ActiveCampaign
     * @var string
     */
    private string $key;

    /**
     * ActiveCampaign actid.
     * @var string
     */
    private string $actId;

    /**
     * ActiveCampaign apiKey
     * @var string
     */
    private string $apiKey;

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'Active Campaign';
    }


    /**
     * Checks if a contact exists by the email ID. Returns the User ID if it exists and false if it doesn't.
     * 
     * @param string $email
     * @return bool|int
     */
    public function contactExists(string $email): bool|int
    {
        try {
            $result = $this->call('GET', '/api/3/contacts', [], [
                'email' => $email
            ]);

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
     * 
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $phone
     * @return bool
     */
    public function createContact(string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = ['contact' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone
        ]];

        try {
            $this->call('POST', '/api/3/contacts', [
                'Content-Type' => 'application/json'
            ], $body);
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Update contact
     * 
     * @param string $contactId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $phone
     * 
     * @return bool
     */
    public function updateContact(string $contactId, string $email, string $firstName = '', string $lastName = '', string $phone = ''): bool
    {
        $body = ['contact' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone
        ]];

        try {
            $this->call('PUT', '/api/3/contacts/'.$contactId, [
                'Content-Type' => 'application/json'
            ], $body);

            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /** 
     * Delete a contact 
     * 
     * @param string $email
     * @return bool
     */
    public function deleteContact(string $email): bool {
        $contact = $this->contactExists($email);

        if (!$contact) {
            return false;
        }

        try {
            $this->call('DELETE', '/api/3/contacts/'.$contact);
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Account Exists
     * 
     * @param string $name
     * @return bool|int
     */
    public function accountExists(string $name): bool|int
    {
        try {
            $result = $this->call('GET', '/api/3/accounts', [], [
                'search' => $name
            ]);

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
     * 
     * @param string $name
     * @param string $url
     * @param int $ownerId
     * @param array $fields
     * 
     * @return bool
     */
    public function createAccount(string $name, string $url = '', int $ownerId = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerId,
            'fields' => array_values(array_filter($fields, function($value) {
                return $value['fieldValue'] !== '' && $value['fieldValue'] !== null && $value['fieldValue'] !== false;
            }))
        ]];

        try {
            $this->call('POST', '/api/3/accounts', [
                'Content-Type' => 'application/json'
            ], $body);
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Update an account
     * 
     * @param string $accountId
     * @param string $name
     * @param string $url
     * @param int $ownerId
     * @param array $fields
     * 
     * @return bool
     */
    public function updateAccount(string $accountId, string $name, string $url = '', int $ownerId = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerId,
            'fields' => array_values(array_filter($fields, function($value) {
                return $value['fieldValue'] !== '' && $value['fieldValue'] !== null && $value['fieldValue'] !== false;
            }))
        ]];

        try {
            $this->call('PUT', '/api/3/accounts/'.$accountId, [
                'Content-Type' => 'application/json',
            ], array_filter($body));
            return true;
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Delete an account
     * 
     * @param string $accountId
     * 
     * @return bool
     */
    public function deleteAccount(string $accountId): bool
    {
        try {
            $this->call('DELETE', '/api/3/accounts/'.$accountId);
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
     * 
     * @param string $accountId
     * @param string $contactId
     * @param string $role
     * 
     * @return bool
     */
    public function syncAssociation(string $accountId, string $contactId, string $role = ''): bool
    {
        // See if the association already exists

        try {
            $result = $this->call('GET', '/api/3/accountContacts', [], [
                'filters[account]' => $accountId,
                'filters[contact]' => $contactId
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }

        if (intval(json_decode($result, true)['meta']['total']) > 0) {
            // Update the association
            $associationId = intval((json_decode($result, true))['accountContacts'][0]['id']);

            try {
                $result = $this->call('PUT', '/api/3/accountContacts/'.$associationId, [
                    'Content-Type' => 'application/json',
                ], [
                    'accountContact' => [
                        'jobTitle' => $role
                    ]
                ]);
                return true;
            } catch (\Exception $e) {
                $this->logError($e);
                return false;
            }
        } else {
            // Create the association
            $result = $this->call('POST', '/api/3/accountContacts', [
                'Content-Type' => 'application/json',
            ], ['accountContact' => [
                'account' => $accountId,
                'contact' => $contactId,
                'jobTitle' => $role
            ]]);

            return true;
        }
    }

    /**
     * @param string $key 
     * @param string $actId
     * @param string $apiKey
     * @param string $organisationId
     * 
     * @return ActiveCampaign
     */
    public function __construct(string $key, string $actId, string $apiKey, string $organisationId)
    {
        $this->key = $key;
        $this->actId = $actId;
        $this->apiKey = $apiKey;
        $this->endpoint = 'https://' . $organisationId . '.api-us1.com/'; // ActiveCampaign API URL, Refer to https://developers.activecampaign.com/reference/url for more details.
        $this->headers = [
            'Api-Token' => $this->apiKey,
            'Content-Type' => null
        ];
    }

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    public function send(Event $event): bool 
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'key' => $this->key,
            'event' => $event->getName(),
            'actid' => $this->actId,
            'eventdata' => json_encode($event->getProps()),
            'visit' => json_encode(['email' => $event->getProp('email')]),
        ];
        
        $query = array_filter($query, fn($value) => !is_null($value) && $value !== '');

        $this->call('POST', 'https://trackcmp.net/event', [], $query); // Active Campaign event URL, Refer to https://developers.activecampaign.com/reference/track-event/ for more details
        return true;
    }

    /**
     * Sets the client IP address.
     * 
     * @param string $ip The IP address to use.
     * 
     * @return self
     */
    public function setClientIP(string $clientIP): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Sets the client user agent.
     * 
     * @param string $userAgent The user agent to use.
     * 
     * @return self
     */
    public function setUserAgent(string $userAgent): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Set Tags
     * 
     * @param string $contactId
     * @param array $tags
     * 
     * @return bool
     */
    public function setTags(string $contactId, array $tags): bool
    {
        foreach ($tags as $tag) {
            try {
                $this->call('POST', '/api/3/contactTags', [
                    'Content-Type' => 'application/json',
                ], [
                    'contactTag' => [
                        'contact' => $contactId,
                        'tag' => $tag
                    ]
                ]);
            } catch (\Exception $e) {
                $this->logError($e);
                return false;
            }
        }

        return true;
    }
}
