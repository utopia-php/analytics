<?php

namespace Utopia\Analytics\Adapter;

use Exception;
use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class Mixpanel extends Adapter
{

    /**
     * Endpoint for MixPanel Events
     */
    public string $endpoint = 'https://api.mixpanel.com/';

    /**
     * API Key
     */
    private string $token;

    /**
     * Mixpanel constructor.
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
        $this->headers = [
            'content-type' => 'application/json',
            'accept' => 'text/plain'
        ];
    }

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'Mixpanel';
    }

    /**
     * Creates an Event on the remote analytics platform. Requires distinct_id prop.
     */
    public function send(Event $event): bool
    {
        if (! $event->getProp('distinct_id')) {
            return false;
        }

        $properties = [
            'token' => $this->token,
            'time' => $event->getProp('time'),
            'distinct_id' => $event->getProp('distinct_id'),
        ];

        foreach ($event->getProps() as $key => $value) {
            if (! isset($properties[$key])) {
                $properties[$key] = $value;
            }
        }

        $payload = [
            'event' => $event->getName(),
            'properties' => $properties,
        ];

        $this->call('POST', '/track', [], $payload);

        return true;
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

    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($event->getProp('distinct_id'))) {
            throw new Exception('Distinct id is required for Mixpanel event');
        }

        if (! $this->send($event)) {
            throw new Exception('Failed to send Mixpanel event');
        }

        return true;
    }
}
