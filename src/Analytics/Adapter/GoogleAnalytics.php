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
     *  Endpoint for Google Analytics
     *  @var string
     */
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
     * @param string $cid
     * Adapter configuration
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
            'ec' => $event->getProp('category'),
            'ea' => $event->getProp('action'),
            'el' => $event->getName(),
            'ev' => $event->getValue(),
            'dh' => parse_url($event->getUrl())['host'],
            'dp' => parse_url($event->getUrl())['path'],
            'dt' => $event->getProp('documentTitle'),
            't' => $event->getType()
        ];
        
        $query = array_filter($query, fn($value) => !is_null($value) && $value !== '');

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

        if (curl_error($ch) !== '') {
            return false;
        }

        curl_close($ch);

        return true;
    }
}
