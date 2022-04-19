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
     * ActiveCampaign Organisation ID
     * @var string
     */
    private string $organisationID;

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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://'.$this->organisationID.'.api-us1.com/api/3/contacts?'.http_build_query([
            'email' => $email
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Token: '.$this->apiKey
        ]);

        $body = curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }

        if (json_decode($body, true)['meta']['total'] > 0) {
            return (json_decode($body, true))['contacts'][0]['id'];
        } else {
            return false;
        }
    }

    /**
     * Create a contact
     * 
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return bool
     */
    public function createContact(string $email, string $firstName, string $lastName): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://'.$this->organisationID.'.api-us1.com/api/3/contacts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['contact' => [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName
        ]]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Api-Token: '.$this->apiKey
        ]);

        curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($statusCode == 201) {
            return true;
        } else {
            return false;
        }
    }

    /** 
     * Delete a contact 
     * 
     * @param string $email
     * @return bool
     */
    public function deleteContact($email): bool {
        $contact = $this->contactExists($email);

        if (!$contact) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://'.$this->organisationID.'.api-us1.com/api/3/contacts/'.$contact);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Token: '.$this->apiKey
        ]);

        curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $key 
     * @param string $actid
     * Adapter configuration
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query($query)
        );

        $body = curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        curl_close($ch);

        return true;
    }
}
