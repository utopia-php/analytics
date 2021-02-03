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

namespace Utopia\Analytics;

use Exception;
use Utopia\Analytics\Analytics;

class GoogleAnalytics extends Analytics
{
    private const URL = 'https://www.google-analytics.com/collect';

    private $tid;
    private $cid;

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
     * Sends an event to Google Analytics.
     * 
     * @param string $category
     * Specifies the event category.
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
     * 
     * @throws Exception
     */
    public function createEvent(string $category, string $action, string $label = null, int $value = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'ec' => $category,
            'ea' => $action,
            'el' => $label,
            'ev' => $value,
            't' => 'event'
        ];

        $this->execute($query);
        return true;
    }

    /**
     * Sends a page view to Google Analytics.
     * 
     * @param string $hostname
     * Specifies the hostname from which content was hosted.
     * 
     * @param string $page
     * The path portion of the page URL. Should begin with '/'.
     * 
     * @param string $title
     * The title of the page / document.
     * 
     * @return bool
     * 
     * @throws Exception
     */
    public function createPageView(string $hostname, string $page, string $title = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query =  [
            'dh' => $hostname,
            'dp' => $page,
            'dt' => $title,
            't' => 'pageview'
        ];

        $this->execute($query);
        return true;
    }

    private function execute(array $query)
    {
        $ch = curl_init();

        $this->prepareCurl($ch, $query);

        curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error:' . curl_error($ch));
        }

        curl_close($ch);
    }

    private function prepareCurl(&$ch, array $query)
    {
        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
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
    }
}
