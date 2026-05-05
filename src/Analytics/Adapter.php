<?php

namespace Utopia\Analytics;

use Exception;
use Utopia\Fetch\Client;
use Utopia\Fetch\Exception as FetchException;

abstract class Adapter
{
    protected bool $enabled = true;

    /**
     * Useragent to use for requests
     */
    protected string $userAgent = 'Utopia PHP Framework';

    /**
     * The IP address to forward to Plausible
     */
    protected string $clientIP;

    /**
     * Endpoint
     */
    protected string $endpoint;

    /**
     * Gets the name of the adapter.
     */
    abstract public function getName(): string;

    /**
     * Global Headers
     *
     * @var array
     */
    protected $headers = [
        'Content-Type' => '',
    ];

    /**
     * Enables tracking for this instance.
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disables tracking for this instance.
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Send the event to the adapter.
     */
    abstract public function send(Event $event): bool;

    /**
     * Validate the adapter.
     * Sends a test event to the adapter and validates if it was received.
     *
     * Throws an exception if the adapter is not valid.
     *
     * @throws Exception
     */
    abstract public function validate(Event $event): bool;

    /**
     * Sets the client IP address.
     *
     * @param  string  $clientIP  The IP address to use.
     */
    public function setClientIP(string $clientIP): self
    {
        $this->clientIP = $clientIP;

        return $this;
    }

    /**
     * Sets the client user agent.
     *
     * @param  string  $userAgent  The user agent to use.
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function createEvent(Event $event): bool
    {
        return $this->send($event);
    }

    /**
     * Call
     *
     * Make an API call
     *
     *
     * @throws Exception
     */
    public function call(string $method, string $path = '', array $headers = [], array $params = []): array|string
    {
        $headers = array_merge($this->headers, $headers);
        $url = str_contains($path, 'http') ? $path : $this->endpoint.$path.(($method == 'GET' && ! empty($params)) ? '?'.http_build_query($params) : '');

        switch ($headers['Content-Type']) {
            case 'application/json':
                $query = json_encode($params);
                break;

            case 'multipart/form-data':
                $query = $this->flatten($params);
                break;

            default:
                $query = http_build_query($params);
                break;
        }

        $client = (new Client)
            ->setUserAgent(php_uname('s').'-'.php_uname('r').':php-'.phpversion())
            ->setAllowRedirects(true);

        foreach ($headers as $key => $value) {
            $client->addHeader($key, $value);
        }

        try {
            $response = $client->fetch(
                url: $url,
                method: $method,
                body: $method !== 'GET' ? $query : [],
            );
        } catch (FetchException $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }

        $responseHeaders = $response->getHeaders();
        $responseStatus = $response->getStatusCode();
        $responseBody = $response->getBody();
        $responseType = trim(explode(';', $responseHeaders['content-type'] ?? '')[0]);

        if ($responseType === 'application/json') {
            $responseBody = json_decode($responseBody, true);
        }

        if ($responseStatus >= 400) {
            if (is_array($responseBody)) {
                throw new Exception(json_encode($responseBody), $responseStatus);
            } else {
                throw new Exception($responseStatus.': '.$responseBody, $responseStatus);
            }
        }

        return $responseBody;
    }

    /**
     * Flatten params array to PHP multiple format
     */
    protected function flatten(array $data, string $prefix = ''): array
    {
        $output = [];

        foreach ($data as $key => $value) {
            $finalKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $output += $this->flatten($value, $finalKey); // @todo: handle name collision here if needed
            } else {
                $output[$finalKey] = $value;
            }
        }

        return $output;
    }
}
