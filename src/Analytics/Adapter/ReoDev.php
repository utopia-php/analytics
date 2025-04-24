<?php

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class ReoDev extends Adapter
{
    /**
     * Endpoint for ReoDev Product API
     */
    protected string $endpoint = 'https://ingest.reo.dev/api/product/usage';

    /**
     * API Key
     */
    protected string $apiKey;

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
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Sends an event to the ReoDev Product API. Requires 'email' prop.
     */
    public function send(Event $event): bool
    {
        if (! $event->getProp('email')) {
            return false;
        }

        $meta = $event->getProps();
        unset($meta['email']);
        unset($meta['account']);
        $payload = [
            'activity_type' => $event->getType(),
            'source' => 'PRODUCT_CLOUD',
            'user_id' => $event->getProp('email'),
            'user_id_type' => 'EMAIL',
            'ip_addr' => $this->clientIP,
            'event_at' => time(),
            'product_id' => $event->getProp('account'),
            'user_agent' => $this->userAgent,
            'meta' => $meta,
        ];

        $payload = array_filter($payload, fn ($value) => ! is_null($value));

        $body = ['payload' => $payload];

        $this->call('POST', $this->endpoint, [
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
        ], $body);

        return true;
    }

    /**
     * Validates the event.
     *
     * @param  Event  $event  The event to validate.
     */
    public function validate(Event $event): bool
    {
        return ! empty($event->getProp('email'));
    }
}
