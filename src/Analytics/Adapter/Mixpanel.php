<?php

namespace Utopia\Analytics\Adapter;

use Exception;
use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;
use Sahils\UtopiaFetch\Client;

class Mixpanel extends Adapter
{
    /**
     * Endpoint for MixPanel Events
     */
    public string $endpoint = 'https://api.mixpanel.com';

    /**
     * API Key
     */
    private string $token;

    /**
     * Mixpanel constructor.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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
        if (! $event->getProp('email')) {
            throw new Exception('Failed to send event - missing `email` prop');
        }

        $properties = [
            'token' => $this->token,
            'time' => $event->getProp('time') ?? microtime(true),
            'distinct_id' => $event->getProp('email'),
            'type' => $event->getType(),
            'url' => $event->getUrl(),
        ];

        $properties = array_filter($properties, function ($value) {
            if (is_array($value)) {
                return ! empty($value);
            }

            return ! is_null($value) && $value !== '';
        });

        foreach ($event->getProps() as $key => $value) {
            if (! isset($properties[$key]) && ! is_null($value) && $value !== '' && ! empty($value)) {
                $properties[$key] = $value;
            }
        }

        $payload = [[
            'event' => $event->getName(),
            'properties' => $properties,
        ]];

        $headers = [
            'Content-Type' => 'application/json',
            'accept' => 'text/plain',
        ];
        $res = Client::fetch(
            url: $this->endpoint.'/track',
            method: 'POST',
            headers: $headers,
            body: $payload
        )->getBody();

        if ($res !== '1') {
            throw new Exception('Failed to send event for '.$event->getProp('email'));
        }

        return true;
    }

    public function createProfile(string $distinctId, string $ip, array $properties = []): bool
    {
        if (empty($properties)) {
            return false;
        }

        $payload = [[
            '$token' => $this->token,
            '$distinct_id' => $distinctId,
            '$set' => $properties,
        ]];

        if (! empty($ip)) {
            $payload[0]['$ip'] = $ip;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'accept' => 'text/plain',
        ];

        $res = Client::fetch(
            method: 'POST',
            url: $this->endpoint.'/engage#profile-set',
            headers: $headers,
            body: $payload
        );

        if ($res !== '1') {
            return false;
        }

        return true;
    }

    public function appendProperties(string $distinctId, array $properties): bool
    {
        if (empty($properties)) {
            return false;
        }

        $payload = [[
            '$token' => $this->token,
            '$distinct_id' => $distinctId,
            '$union' => $properties,
        ]];

        $headers = [
            'Content-Type' => 'application/json',
            'accept' => 'text/plain',
        ];

        $res = Client::fetch(
            method: 'POST',
            url: $this->endpoint.'/engage#profile-union',
            headers: $headers,
            body: $payload
        );

        if ($res !== '1') {
            return false;
        }

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
