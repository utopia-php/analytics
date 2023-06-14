<?php

/**
 * Utopia PHP Framework
 *
 *
 * @link https://github.com/utopia-php/framework
 *
 * @version 1.0 RC1
 *
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Exception;
use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;
use Sahils\UtopiaFetch\Client;

class Plausible extends Adapter
{
    /**
     *  Endpoint for Plausible
     */
    protected string $endpoint = 'https://plausible.io/api';

    /**
     * Global Headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Plausible API key
     */
    protected string $apiKey;

    /**
     * Domain to use for events
     */
    protected string $domain;

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'Plausible';
    }

    /**
     * Constructor.
     *
     * @param  string  $domain    The domain to use for events
     * @param  string  $apiKey    The API key to use for requests
     * @param  string  $useragent The useragent to use for requests
     * @param  string  $clientIP  The IP address to forward to Plausible
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
     * @param  Event  $event The event to send.
     */
    public function send(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (! $this->provisionGoal($event->getType())) {
            return false;
        }

        $params = [
            'url' => $event->getUrl(),
            'props' => $event->getProps(),
            'domain' => $this->domain,
            'name' => $event->getType(),
            'referrer' => $event->getProp('referrer'),
            'screen_width' => $event->getProp('screenWidth'),
        ];

        $headers = [
            'X-Forwarded-For' => $this->clientIP,
            'User-Agent' => $this->userAgent,
            'Content-Type' => 'application/json',
        ];

        Client::fetch(
            url: $this->endpoint.'/event',
            method: 'POST',
            headers: $headers,
            body: $params
        );

        return true;
    }

    /**
     * Provision a goal for the given event.
     *
     * @param  string  $eventName The name of the event.
     */
    private function provisionGoal(string $eventName): bool
    {
        $params = [
            'site_id' => $this->domain,
            'goal_type' => 'event',
            'event_name' => $eventName,
        ];

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];

        Client::fetch(
            url: $this->endpoint.'/v1/sites/goals',
            method: 'PUT',
            headers: $headers,
            body: $params
        );

        return true;
    }

    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($event->getType())) {
            throw new Exception('Event type is required');
        }

        if (empty($event->getUrl())) {
            throw new Exception('Event URL is required');
        }

        $validateURL = $this->endpoint.'/v1/stats/aggregate?'.http_build_query([
            'site_id' => $this->domain,
            'filters' => json_encode(['goal' => $event->getName()]),
        ]);
        $checkCreated = Client::fetch(
            url: $validateURL,
            headers: [
                'Content-Type' => '',
                'Authorization' => 'Bearer '.$this->apiKey,
            ]
        )->getBody();
        $checkCreated = json_decode($checkCreated, true);

        if (! isset($checkCreated['results']['visitors']['value'])) {
            throw new Exception('Failed to validate event');
        }

        return $checkCreated['results']['visitors']['value'] > 0;
    }
}
