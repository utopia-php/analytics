<?php

/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class Plausible extends Adapter
{
    /**
     *  Endpoint for Plausible
     *  @var string
     */
    protected string $endpoint = 'https://plausible.io/api/event';

    /**
     * Useragent to use for requests
     * @var string
     */
    protected string $userAgent = 'Utopia PHP Framework';

    /**
     * Headers to use for events
     * @var array
     */
    protected array $headers;

    /**
     * Domain to use for events
     * 
     * @var string
     */
    protected string $domain;
    

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'Plausible';
    }

    /**
     * @param string $configuration
     * Adapter configuration
     * 
     * @return Plausible
     */

    public function __construct(string $configuration)
    {
        $data = explode(',', $configuration);
        $data = array_map(function($item) {
            return explode('=', $item);
        }, $data);
        $data = array_combine(array_column($data, 0), array_column($data, 1));

        $this->domain = $data['domain'];

        $this->userAgent = $data['useragent'];

        $this->headers = array(' X_FORWARDED_FOR: '  . $data['clientIP'], ' Content-Type: application/json ');
    }

    /**
     * Sends an event to Plausible.
     * 
     * @param Event $event
     * 
     * @return bool
     */
    public function createEvent(Event $event): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'url' => $event->getUrl(),
            'props' => $event->getProps(),
            'domain' => $this->domain,
            'name' => 'event'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
    
        curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        curl_close($ch);
    
        return true;
    }
}
