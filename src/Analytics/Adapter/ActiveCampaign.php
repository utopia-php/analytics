<?php

// Note: ActiveCampaign requires the email prop to be set.
// It also won't create contacts, it'll only add events to pre-existing contacts.

/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Torsten Dittmann <torsten@appwrite.io>
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;
use Utopia\CLI\Console;

class ActiveCampaign extends Adapter
{
    /**
     *  Endpoint for ActiveCampaign
     *  @var string
     */
    public string $endpoint = 'https://trackcmp.net/event';

    /**
     * Event Key for ActiveCampaign
     * @var string
     */
    private string $key;

    /**
     * ActiveCampaign actid.
     * @var string
     */
    private string $actid;

    /**
     * ActiveCampaign apiKey
     * @var string
     */
    private string $apiKey;

    /**
     * ActiveCampaign Email
     * @var string
     */
    private string $email;

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'ActiveCampaign';
    }


    /**
     * Checks if a contact exists and returns a bool if it does.
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
            Console::error($e->getMessage());
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
            Console::error($e->getMessage());
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
            Console::error($e->getMessage());
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
            Console::error($e->getMessage());
            return false;
        }
    }

    /**
     * Account Exists
     * 
     * @param string $domain
     * @return bool|int
     */
    public function accountExists($name): bool|int
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
            Console::error($e->getMessage());
            return false;
        }
    }

    /**
     * Create an account
     * 
     * @param string $name
     * @param string $url
     * @param int $ownerID
     * @param array $fields
     * 
     * @return bool
     */
    public function createAccount(string $name, string $url = '', int $ownerID = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerID,
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
            Console::error($e->getMessage());
            return false;
        }
    }

    /**
     * Update an account
     * 
     * @param string $accountId
     * @param string $name
     * @param string $url
     * @param int $ownerID
     * @param array $fields
     * 
     * @return bool
     */
    public function updateAccount(string $accountId, string $name, string $url = '', int $ownerID = 1, array $fields = []): bool
    {
        $body = ['account' => [
            'name' => $name,
            'accountUrl' => $url,
            'owner' => $ownerID,
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
            Console::error($e->getMessage());
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
            Console::error($e->getMessage());
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
            Console::error($e->getMessage());
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
                Console::error($e->getMessage());
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
     * @param string $actid
     * @param string $apiKey
     * @param string $organisationID
     * @param string $email
     * 
     * @return ActiveCampaign
     */
    public function __construct(string $key, string $actid, string $apiKey, string $organisationID, string $email)
    {
        $this->key = $key;
        $this->actid = $actid;
        $this->email = $email;
        $this->apiKey = $apiKey;
        $this->organisationID = $organisationID;
        $this->endpoint = 'https://'.$organisationID.'.api-us1.com/';
        $this->headers = [
            'api-token' => $this->apiKey,
            'Content-Type' => null
        ];
    }

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    public function createEvent(Event $event): bool 
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'key' => $this->key,
            'event' => $event->getName(),
            'actid' => $this->actid,
            'eventdata' => json_encode($event->getProps()),
            'visit' => json_encode(['email' => $event->getProp('email')]),
        ];
        
        $query = array_filter($query, fn($value) => !is_null($value) && $value !== '');

        try {
            $this->call('POST', 'https://trackcmp.net/event', [], $query);
            return true;
        } catch (\Exception $e) {
            Console::error($e->getMessage());
            return false;
        }
    }
}
