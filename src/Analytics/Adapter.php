<?php

namespace Utopia\Analytics;

use Exception;
use Utopia\CLI\Console;

abstract class Adapter
{
    protected bool $enabled = true;

    /**
     * Useragent to use for requests

     * @var string
     */
    protected string $userAgent = 'Utopia PHP Framework';

    /**
     * The IP address to forward to Plausible
     * 
     * @var string
     */
    protected string $clientIP;

    /**
     * Gets the name of the adapter.
     * 
     * @return string
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
     * 
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disables tracking for this instance.
     * 
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Send the event to the adapter.
     * 
     * @param Event $event
     * @return bool
     */
    public abstract function send(Event $event): bool;

    /**
     * Sets the client IP address.
     * 
     * @param string $clientIP The IP address to use.
     * 
     * @return self
     */
    public function setClientIP(string $clientIP): self
    {
        $this->clientIP = $clientIP;
        return $this;
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
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    public function createEvent(Event $event): bool
    {
        try {
            return $this->send($event);
        } catch (\Exception $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Call
     *
     * Make an API call
     *
     * @param string $method
     * @param string $path
     * @param array $params
     * @param array $headers
     * @return array|string
     * @throws \Exception
     */
    public function call(string $method, string $path = '', array $headers = array(), array $params = array()): array|string
    {
        $headers            = array_merge($this->headers, $headers);
        $ch                 = curl_init((str_contains($path, 'http') ? $path : $this->endpoint . $path . (($method == 'GET' && !empty($params)) ? '?' . http_build_query($params) : '')));
        $responseHeaders    = [];
        $responseStatus     = -1;
        $responseType       = '';
        $responseBody       = '';

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

        foreach ($headers as $i => $header) {
            $headers[] = $i . ':' . $header;
            unset($headers[$i]);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, php_uname('s') . '-' . php_uname('r') . ':php-' . phpversion());
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', strtolower($header), 2);

            if (count($header) < 2) { // ignore invalid headers
                return $len;
            }

            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);

            return $len;
        });

        if($method != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $responseBody   = curl_exec($ch);

        $responseType   = $responseHeaders['Content-Type'] ?? '';
        $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        switch(substr($responseType, 0, strpos($responseType, ';'))) {
            case 'application/json':
                $responseBody = json_decode($responseBody, true);
            break;
        }

        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        
        curl_close($ch);

        if($responseStatus >= 400) {
            if(is_array($responseBody)) {
                throw new \Exception(json_encode($responseBody));
            } else {
                throw new \Exception($responseStatus . ': ' . $responseBody);
            }
        }


        return $responseBody;
    }

    /**
     * Flatten params array to PHP multiple format
     *
     * @param array $data
     * @param string $prefix
     * @return array
     */
    protected function flatten(array $data, string $prefix = ''): array {
        $output = [];

        foreach($data as $key => $value) {
            $finalKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $output += $this->flatten($value, $finalKey); // @todo: handle name collision here if needed
            }
            else {
                $output[$finalKey] = $value;
            }
        }

        return $output;
    }

    /**
     * Log Error
     * 
     * @param Exception $e
     * @return void
     */
    protected function logError(Exception $e) {
        Console::error('[Error] ' . $this->getName() . ' Error: ');
        Console::error('[Error] Type: ' . get_class($e));
        Console::error('[Error] Message: ' . $e->getMessage());
        Console::error('[Error] File: ' . $e->getFile());
        Console::error('[Error] Line: ' . $e->getLine());
    }
}