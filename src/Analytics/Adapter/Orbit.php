<?php

/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Orbit
 *
 * @link https://github.com/utopia-php/framework
 * @author Torsten Dittmann <torsten@appwrite.io>
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class Orbit extends Adapter
{
    /**
     * Endpoint for Orbit Events
     * @var string
     */
    public string $endpoint = 'https://app.orbit.love/api/v1/';

    /**
     * API Key
     * @var string
     */
    private string $apiKey;

    /**
     * The source of the analytic data
     * @var string
     */
    private string $source;

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
     * @param string $workspaceId 
     * @param string $apiKey
     * Adapter configuration
     * 
     * @return Orbit
     */
    public function __construct(string $workspaceId, string $apiKey, string $source)
    {
        $this->endpoint = $this->endpoint . $workspaceId;
        $this->apiKey = $apiKey;
        $this->source = $source;
    }

    /**
     * Creates an Event on the remote analytics platform. Requires email prop.
     * 
     * @param Event $event
     * @return bool
     */
    public function send(Event $event): bool
    {
        if (!$event->getProp('email')) {
            return false;
        }

        $activity = [
            'title' => $event->getName(),
            'activity_type_key' => $event->getType()
        ];

        $identity = [
            "source" => $this->source,
            "email" => $event->getProp('email'),
            "username" => $event->getProp('username')
        ];

        $activity = array_filter($activity, fn ($value) => !is_null($value) && $value !== '');
        $identity = array_filter($identity, fn ($value) => !is_null($value) && $value !== '');

        $this->call('POST', $this->endpoint . '/activities', [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey
        ], [
            'activity' => $activity,
            'identity' => $identity
        ]);

        return true;
    }
}
