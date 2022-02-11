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
        return 'GoogleAnalytics';
    }

    /**
     * @param string $domain
     * The Plausible domain to use for sending events
     * 
     * @param string $useragent
     * The Useragent to use for sending events
     * 
     * @param string $clientIP
     * Specifies the  client's IP address used for CLI mode
     * 
     * @return Plausible
     */

    public function __construct(string $domain, string $useragent, string $clientIP = '127.0.0.1')
    {
        $this->domain = $domain;

        $this->userAgent = $useragent;

        $this->headers = array(' X_FORWARDED_FOR: '  . $clientIP, ' Content-Type: application/json ');
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

        curl_setopt($ch, CURLOPT_URL, $this->domain);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
    
        curl_exec($ch);
        curl_close($ch);
    
        return true;
    }
}
