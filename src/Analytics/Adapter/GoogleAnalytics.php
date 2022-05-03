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
use Utopia\CLI\Console;

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

        if ($event->getType() !== 'pageview') {
            $event->setProps( array_merge($event->getProps(), ['action' => $event->getType()]));
            $event->setType('event');
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

        try {
            $result = $this->call('POST', $this->endpoint, [], array_merge([
                'tid' => $this->tid,
                'cid' => $this->cid,
                'v' => 1
            ], $query));
        } catch (\Exception $e) {
            Console::error('[Error] Google Analytics Error: ');
            Console::error('[Error] Type: ' . get_class($e));
            Console::error('[Error] Message: ' . $e->getMessage());
            Console::error('[Error] File: ' . $e->getFile());
            Console::error('[Error] Line: ' . $e->getLine());
            return false;
        }

        // Parse Debug data
        if ($this->endpoint == "https://www.google-analytics.com/debug/collect") {
            return json_decode($result, true)["hitParsingResult"][0]["valid"];
        }

        return true;
    }
}
