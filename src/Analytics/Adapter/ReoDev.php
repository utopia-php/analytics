<?php

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class ReoDev extends Adapter
{
    /**
     * Endpoint for ReoDev API
     */
    public string $endpoint = 'https://ingest.reo.dev/api/';

    /**
     * Email of the reodev account
     */
    private string $email;

    /**
     * API Key
     */
    private string $apiKey;

    /**
     * List ID
     */
    private string $listId;

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'ReoDev';
    }

    /**
     * @return ReoDev
     */
    public function __construct(string $email, string $apiKey, string $listId)
    {
        $this->email = $email;
        $this->apiKey = $apiKey;
        $this->listId = $listId;
    }

    /**
     * Adds a new developer on the remote analytics platform. Requires email prop.
     */
    public function send(Event $event): bool
    {
        if (! $event->getProp('email')) {
            return false;
        }

        $data = $event->getProps();
        unset($data['email']);
        unset($data['name']);
        unset($data['account']);

        $data = json_encode($data);

        $body = [
            'type' => 'DEVELOPER',
            'entities' => [
                [
                    'primaryKey' => $event->getProp('email'),
                    'clientKey' => 'email',
                    'fieldType' => 'String',
                    'companyData' => [
                        'name' => $event->getProp('name'),
                        'action' => $event->getType(),
                        'label' => $event->getName(),
                        'url' => $event->getUrl(),
                        'account' => $event->getProp('account'),
                        'data' => $data,
                    ],
                ],
            ],
        ];

        $this->call('PUT', $this->endpoint.'/product/list/'.$this->listId, [
            'Content-Type' => 'application/json',
            'x-api-key' => $this->apiKey,
            'user' => $this->email,
        ], $body);

        return true;
    }

    /**
     * Sets the client IP address.
     *
     * @param  string  $ip  The IP address to use.
     */
    public function setClientIP(string $clientIP): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Sets the client user agent.
     *
     * @param  string  $userAgent  The user agent to use.
     */
    public function setUserAgent(string $userAgent): self
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Validates the event.
     *
     * @param  Event  $event  The event to validate.
     */
    public function validate(Event $event): bool
    {
        throw new \Exception('Not implemented');
    }
}
