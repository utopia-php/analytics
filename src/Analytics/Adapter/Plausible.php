<?php

/**
 * Utopia PHP Framework
 *
 * @package Analytics
 * @subpackage Tests
 *
 * @link https://github.com/utopia-php/framework
 * @author Torsten Dittmann <torsten@appwrite.io>
 * @version 1.0 RC1
 * @license The MIT License (MIT) <http://www.opensource.org/licenses/mit-license.php>
 */

namespace Utopia\Analytics\Adapter;

use Utopia\Analytics\Adapter;

class Plausible extends Adapter
{
    public $endpoint = 'https://plausible.io/api';

    private $domain;

    /**
     * @param string $domain 
     * The domain name of the site in Plausible
     * 
     * @return Plausible
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Sends an event to Plausible.
     * 
     * @param string $url
     * The path portion of the page URL. Should begin with '/'.
     * 
     * @param string $action
     * Specifies the event action.
     * 
     * @param string $label
     * Specifies the event label.
     * 
     * @param string $value
     * Specifies the event value. Values must be non-negative.
     * 
     * @return bool
     */
    public function createEvent(string $url, string $action, string $label = null, string $value = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'domain' => $this->domain,
            'name' => $action,
            'url' => $url
        ];

        if (!empty($label) && !empty($value)) {
            $query['props'][$label] = $value;
        }

        $this->execute(self::METHOD_POST, 'event', ['content-type' => 'application/json'], $query);
        return true;
    }

    /**
     * Sends a page view to Plausible.
     * 
     * @param string $hostname
     * Specifies the hostname from which content was hosted.
     * 
     * @param string $page
     * The path portion of the page URL. Should begin with '/'.
     * 
     * @return bool
     */
    public function createPageView(string $hostname, string $page): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query =  [
            'name' => 'pageview',
            'url' => $hostname . $page,
            'domain' => $this->domain
        ];

        $this->execute(self::METHOD_POST, 'event', ['content-type' => 'application/json'], $query);
        return true;
    }
}
