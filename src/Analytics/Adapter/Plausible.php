<?php

/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class Plausible extends Adapter
{
    /**
     *  Endpoint for Plausible
     *  @var string
     */
    protected string $endpoint = 'https://plausible.io/api/event';

    /**
     * Useragent to use for requests
     * @var string
     */
    protected string $userAgent = 'Utopia PHP Framework';

    /**
     * Plausible API key
     * @var string
     */
    protected string $apiKey;

    /**
     * Headers to use for events
     * @var array
     */
    protected array $headers;

    /**
     * Domain to use for events
     * 
     * @var string
     */
    protected string $domain;

    /**
     * The IP address to forward to Plausible
     * 
     * @var string
     */
    protected string $clientIP;
    

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'Plausible';
    }

    /**
     * @param string $domain
     * @param string $apiKey
     * @param string $useragent
     * @param string $clientIP
     * 
     * @return Plausible
     */

    public function __construct(string $domain, string $apiKey, string $useragent, string $clientIP)
    {
        $this->domain = $domain;

        $this->apiKey = $apiKey;

        $this->userAgent = $useragent;

        $this->clientIP = $clientIP;
    }

    /**
     * Sends an event to Plausible.
     * 
     * @param Event $event
     * 
     * @return bool
     */
    public function createEvent(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->provisionGoal($event->getName())) {
            return false;
        }

        $query = [
            'url' => $event->getUrl(),
            'props' => $event->getProps(),
            'domain' => $this->domain,
            'name' => $event->getType(),
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);


        $headers = [
            'X-Forwarded-For: '  . $this->clientIP,
            'Content-Type: application/json',

        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    
        curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($statusCode !== 202) {
            return false;
        }
    
        return true;
    }

    private function provisionGoal(string $eventName)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://plausible.io/api/v1/sites/goals");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded', 
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'site_id' => $this->domain,
            'goal_type' => 'event',
            'event_name' => $eventName,
        ]));

        curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            return false;
        }

        return true;
    }
}
