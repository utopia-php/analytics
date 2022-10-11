<?php

/**
 * Utopia PHP Framework
 *
 *
 * @link https://github.com/utopia-php/framework
 *
 * @author Torsten Dittmann <torsten@appwrite.io>
 *
 * @version 1.0 RC1
 *
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class Orbit extends Adapter
{
    /**
     * Endpoint for Orbit Events
     *
     * @var string
     */
    public string $endpoint = 'https://app.orbit.love/api/v1/';

    /**
     * API Key
     *
     * @var string
     */
    private string $apiKey;

    /**
     * dataOrigin is where this analytic data originates from.
     *
     * @var string
     */
    private string $dataOrigin;

    /**
     * Gets the name of the adapter.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Orbit';
    }

    /**
     * @param  string  $workspaceId
     * @param  string  $apiKey
     * @param  string  $dataOrigin
     * @return Orbit
     */
    public function __construct(string $workspaceId, string $apiKey, string $dataOrigin)
    {
        $this->endpoint = $this->endpoint.$workspaceId;
        $this->apiKey = $apiKey;
        $this->dataOrigin = $dataOrigin;
    }

    /**
     * Creates an Event on the remote analytics platform. Requires email prop.
     *
     * @param  Event  $event
     * @return bool
     */
    public function send(Event $event): bool
    {
        if (! $event->getProp('email')) {
            return false;
        }

        $activity = [
            'title' => $event->getName(),
            'activity_type_key' => $event->getType(),
        ];

        $identity = [
            'source' => $this->dataOrigin,
            'email' => $event->getProp('email'),
            'username' => $event->getProp('username'),
        ];

        $activity = array_filter($activity, fn ($value) => ! is_null($value) && $value !== '');
        $identity = array_filter($identity, fn ($value) => ! is_null($value) && $value !== '');

        $this->call('POST', $this->endpoint.'/activities', [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ], [
            'activity' => $activity,
            'identity' => $identity,
        ]);

        return true;
    }

    /**
     * Sets the client IP address.
     *
     * @param  string  $ip The IP address to use.
     * @return self
     */
    public function setClientIP(string $clientIP): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Sets the client user agent.
     *
     * @param  string  $userAgent The user agent to use.
     * @return self
     */
    public function setUserAgent(string $userAgent): self
    {
        throw new \Exception('Not implemented');
    }
}
