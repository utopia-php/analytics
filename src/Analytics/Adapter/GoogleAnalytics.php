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
use Utopia\Analytics\Event;

class GoogleAnalytics extends Adapter
{
    /**
    * Endpoint for Google Analytics
    * @var string
    **/
    public string $endpoint = 'https://www.google-analytics.com/collect';

    /**
     * Tracking ID for Google Analytics
     * @var string
     */
    private string $tid;

    /**
     * A unique identifer for Google Analytics
     * @var string
     */
    private string $cid;

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
     * @param string $tid 
     * The tracking ID / web property ID. The format is UA-XXXX-Y. All collected data is associated by this ID.
     * 
     * @param string $cid
     * This pseudonymously identifies a particular user, device, or browser instance.
     * 
     * @return GoogleAnalytics
     */
    public function __construct(string $tid, string $cid)
    {
        $this->tid = $tid;
        $this->cid = $cid;
    }

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    public function createEvent(Event $event): bool 
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'ea' => $event->getProps('action'),
            't' => $event->getType()
        ];

        if (key_exists('category', $event->getProps())) {
            $query['ec'] = $event->getProps()['category'];
        }

        if (!empty($event->getName())) {
            $query['el'] = $event->getName();
        }

        if (!empty($event->getValue())) {
            $query['ev'] = $event->getValue();
        }

        if (!empty(parse_url($event->getUrl())['host'])) {
            $query['dh'] = parse_url($event->getUrl())['host'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(array_merge([
                'tid' => $this->tid,
                'cid' => $this->cid,
                'v' => 1
            ], $query))
        );

        curl_exec($ch);
        curl_close($ch);

        return true;
    }
}
