<?php

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
     * dataOrigin is where this analytic data originates from.
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
     * @param string $workspaceId 
     * @param string $apiKey
     * @param string $dataOrigin
     * 
     * @return Orbit
     */
    public function __construct(string $workspaceId, string $apiKey, string $dataOrigin)
    {
        $this->endpoint = $this->endpoint . $workspaceId;
        $this->apiKey = $apiKey;
        $this->dataOrigin = $dataOrigin;
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

        $tags = is_array($event->getProp('tags')) ? $event->getProp('tags') : [];

        if ($event->getProp('account')) {
            $tags[] = $event->getProp('account');
        }

        if ($event->getProp('code')) {
            $tags[] = $event->getProp('code');
        }

        $activity = [
            'title' => $event->getName(),
            'activity_type_key' => $event->getType(),
            'link' => $event->getUrl(),
            'member' => [
                'email' => $event->getProp('email'),
                'name' => $event->getProp('name'),
                'tags_to_add' => $tags,
            ],
            'properties' => array_map(function ($value) {
                if (is_array($value)) {
                    return json_encode($value);
                }

                return $value;
            }, array_filter($event->getProps(), fn ($value) => !is_null($value) && $value !== '')),
        ];

        unset($activity['properties']['email']);
        unset($activity['properties']['name']);

        $activity = array_filter($activity, fn ($value) => !is_null($value) && $value !== '');

        $this->call('POST', $this->endpoint . '/activities', [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey
        ], [
            'activity' => $activity
        ]);

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

    public function validate(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (empty($event->getType())) {
            return false;
        }

        if (empty($event->getUrl())) {
            return false;
        }

        if (empty($event->getName())) {
            return false;
        }

        if (empty($event->getProp('email'))) {
            return false;
        }

        if (!$this->send($event)) {
            return false;
        }

        // Check if event made it.
        $listMembers = $this->call('GET', '/members/find', [
            'Authorization' => 'Bearer '.$this->apiKey
        ], [
            'source' => 'email',
            'email' => $event->getProp('email'),
        ]);

        $listMembers = json_decode($listMembers, true);

        if (empty($listMembers['data'])) {
            return false;
        }

        $member = $listMembers['data'];

        $activities = $this->call('GET', '/members/'.$member['id'].'/activities', [
            'Authorization' => 'Bearer '.$this->apiKey
        ], [
            'activity_type' => $event->getType(),
        ]);

        $activities = json_decode($activities, true);

        if (empty($activities['data'])) {
            return false;
        }

        $foundActivity = false;

        foreach ($activities['data'] as $activity) {
            if ($activity['attributes']['custom_title'] === $event->getName()) {
                $foundActivity = $activity['id'];
            }
        }

        if (!$foundActivity) {
            return false;
        }

        $this->call('DELETE',  '/members/'.$member['id'].'/activities/'.$foundActivity, [
            'Authorization' => 'Bearer '.$this->apiKey
        ], []);

        return true;
    }
}
