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
    protected string $endpoint = 'https://plausible.io/api';

    /**
     * Useragent to use for requests

     * @var string
     */
    protected string $userAgent = 'Utopia PHP Framework';

    /**
     * Global Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Plausible API key

     * @var string
     */
    protected string $apiKey;

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

        try {
            $this->call('POST', '/event', [
                'X-Forwarded-For' => $this->clientIP,
                'User-Agent' => $this->userAgent,
                'content-type' => 'application/json'
            ], $query);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function provisionGoal(string $eventName)
    {
        try {
            $this->call('PUT', '/v1/sites/goals', [
                'Content-Type' => null,
                'Authorization' => 'Bearer '.$this->apiKey
            ], [
                'site_id' => $this->domain,
                'goal_type' => 'event',
                'event_name' => $eventName,
            ]);
            return true;
        } catch (\Exception $e) {
            throw $e;
            return false;
        }
    }
}
