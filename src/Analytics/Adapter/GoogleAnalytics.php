<?php

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class GoogleAnalytics extends Adapter
{
    /**
     *  Endpoint for Google Analytics
     */
    public string $endpoint = 'https://www.google-analytics.com/collect';

    /**
     * Endpoint for Google Analytics Debug
     */
    public string $debugEndpoint = 'https://www.google-analytics.com/debug/collect';

    /**
     * Tracking ID for Google Analytics
     */
    private string $tid;

    /**
     * A unique identifer for Google Analytics
     */
    private string $cid;

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'Google Analytics';
    }

    /**
     * @param  string  $cid
     * Adapter configuration
     * @return GoogleAnalytics
     */
    public function __construct(string $tid, string $cid)
    {
        $this->tid = $tid;
        $this->cid = $cid;
    }

    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if (empty($event->getType())) {
            throw new \Exception('Event type is required');
        }

        if (empty($event->getUrl())) {
            throw new \Exception('Event URL is required');
        }

        if (empty($event->getName())) {
            throw new \Exception('Event name is required');
        }

        $query = [
            'ec' => $event->getProp('category'),
            'ea' => $event->getProp('action'),
            'el' => $event->getName(),
            'ev' => $event->getValue(),
            'dh' => parse_url($event->getUrl())['host'],
            'dp' => parse_url($event->getUrl())['path'],
            'dt' => $event->getProp('documentTitle'),
            't' => ($event->getType() === 'pageview') ? 'pageview' : 'event',
            'uip' => $this->clientIP ?? '',
            'ua' => $this->userAgent ?? '',
            'sr' => $event->getProp('screenResolution'),
            'vp' => $event->getProp('viewportSize'),
            'dr' => $event->getProp('referrer'),
        ];

        $query = array_filter($query, fn ($value) => ! is_null($value) && $value !== '');

        $validateResponse = $this->call('POST', $this->debugEndpoint, [], array_merge(
            $query,
            [
                'tid' => $this->tid,
                'cid' => $this->cid,
                'v' => 1,
            ]
            ));

        if (!isset($validateResponse) || $validateResponse['hitParsingResult'][0]['valid'] !== true) {
            throw new \Exception('Invalid event');
        }

        return true;
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function send(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        if ($event->getType() !== 'pageview') {
            $event->setProps(array_merge($event->getProps(), ['action' => $event->getType()]));
            $event->setType('event');
        }

        if ($event->getProp('screenWidth') && $event->getProp('screenHeight')) {
            $event->setProps(array_merge($event->getProps(), ['screenResolution' => $event->getProp('screenWidth').'x'.$event->getProp('screenHeight')]));
        }

        $query = [
            'ec' => $event->getProp('category'),
            'ea' => $event->getProp('action'),
            'el' => $event->getName(),
            'ev' => $event->getValue(),
            'dh' => parse_url($event->getUrl())['host'],
            'dp' => parse_url($event->getUrl())['path'],
            'dt' => $event->getProp('documentTitle'),
            't' => $event->getType(),
            'uip' => $this->clientIP ?? '',
            'ua' => $this->userAgent ?? '',
            'sr' => $event->getProp('screenResolution'),
            'vp' => $event->getProp('viewportSize'),
            'dr' => $event->getProp('referrer'),
        ];

        if ($event->getProp('account')) {
            $query['cd1'] = $event->getProp('account');
        }

        $query = array_filter($query, fn ($value) => ! is_null($value) && $value !== '');

        $result = $this->call('POST', $this->endpoint, [], array_merge([
            'tid' => $this->tid,
            'cid' => $this->cid,
            'v' => 1,
        ], $query));

        // Parse Debug data
        if ($this->endpoint == "https://www.google-analytics.com/debug/collect") {
            return $result["hitParsingResult"][0]["valid"];
        }

        return true;
    }
}
