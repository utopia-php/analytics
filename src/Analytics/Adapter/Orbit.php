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

class Orbit extends Adapter
{
    /**
     *  Endpoint for Orbit
     *  @var string
     */
    protected string $endpoint = 'https://app.orbit.love/api/v1';

    /**
     * Workspace ID for Orbit
     * @var string
     */
    protected string $workspace = '';

    /**
     * Orbit Key
     * @var string
     */
    protected $key = '';

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'Orbit';
    }

    /**
     * @param string $workspace
     * @param string $key
     * 
     * @return Orbit
     */
    public function __construct(string $workspace, string $key)
    {
        $this->workspace = $workspace;
        $this->key = $key;
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
            'title' => $event->getName(),
            'activity_type_key' => $event->getType(),
            'link' => $event->getUrl()
        ];
        
        $query = array_filter($query, fn($value) => !is_null($value) && $value !== '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($query)
        );

        curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        curl_close($ch);

        return true;
    }
}
