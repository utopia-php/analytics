<?php

// Note: ActiveCampaign requires the email prop to be set.
// It also won't create contacts, it'll only add events to pre-existing contacts.

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

class ActiveCampaign extends Adapter
{
    /**
     *  Endpoint for ActiveCampaign
     *  @var string
     */
    public string $endpoint = 'https://trackcmp.net/event';

    /**
     * Event Key for ActiveCampaign
     * @var string
     */
    private string $key;

    /**
     * ActiveCampaign actid.
     * @var string
     */
    private string $actid;

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    public function getName(): string
    {
        return 'ActiveCampaign';
    }

    /**
     * @param string $key 
     * Event Key for ActiveCampaign.
     * 
     * @param string $actid
     * ActiveCampaign actid
     * 
     * @return ActiveCampaign
     */
    public function __construct(string $configuration)
    {
        $data = explode(',', $configuration);
        $data = array_map(function($item) {
            return explode('=', $item);
        }, $data);
        $data = array_combine(array_column($data, 0), array_column($data, 1));

        $this->key = $data['key'];
        $this->actid = $data['actid'];
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
            'key' => $this->key,
            'event' => $event->getName(),
            'actid' => $this->actid,
            'eventdata' => json_encode($event->getProps()),
            'visit' => json_encode(['email' => $event->getProp('email')]),
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
            http_build_query($query)
        );

        $body = curl_exec($ch);

        if (curl_error($ch) !== '') {
            return false;
        }

        curl_close($ch);

        return true;
    }
}
