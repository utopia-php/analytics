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

use DateTime;
use Exception;
use Utopia\Analytics\Adapter;

class Orbit extends Adapter
{
    private const URL = 'https://app.orbit.love/api/v1/';

    private $token;
    private $workplaceId;

    /**
     * @param string $token 
     * Your Orbit API Token.
     * 
     * @param string $workplaceId
     * The ID of the workplace you want to send the data to.
     * 
     * @return Orbit
     */
    public function __construct(string $token, string $workplaceId)
    {
        $this->token = $token;
        $this->workplaceId = $workplaceId;
    }

    /**
     * Create a new activity for Orbit.
     * 
     * @param string $description
     * The description of the activity.
     * 
     * @param string $link
     * The link of the activity.
     * 
     * @param string $link_text
     * The text of the link.
     * 
     * @param string $title
     * The title of the activity.
     * 
     * @param string $activity_type_key
     * A key to identify the activity type.
     * 
     * @param string $key
     * A Unique key for identifying the activity.
     * 
     * @param int $occurred_at
     * The unix timestamp of when the activity occoured. If not set, the current time will be used.
     * 
     * @param string $member_id
     * The orbit slug or member ID to identify who performed the activity.
     */
    public function createMemberActivity(string $description, string $link, string $link_text, string $title, string $activity_type_key, string $key, int $occurred_at = null, string $member_id = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'description' => $description,
            'link' => $link,
            'link_text' => $link_text,
            'title' => $title,
            'activity_type_key' => $activity_type_key,
            'key' => $key,
            'occurred_at' => $occurred_at,
        ];

        $this->execute($query, $member_id.'/activities');

        return true;
    }

    /**
     * Create a new activity for Orbit without a member ID.
     * 
     * @param string $description
     * The description of the activity.
     * 
     * @param string $link
     * The link of the activity.
     * 
     * @param string $link_text
     * The text of the link.
     * 
     * @param string $title
     * The title of the activity.
     * 
     * @param string $activity_type_key
     * A key to identify the activity type.
     * 
     * @param string $key
     * A Unique key for identifying the activity.
     * 
     * @param int $occurred_at
     * The unix timestamp of when the activity occoured. If not set, the current time will be used.
     * 
     * @param string $identity_source
     * The source of the identity.
     * 
     * @param string $identity_data
     * The data of the identity.
     */
    public function createActivity(string $description, string $link, string $link_text, string $title, string $activity_type_key, string $key, int $occurred_at = null, string $identity_source = null, string $identity_data = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $query = [
            'description' => $description,
            'link' => $link,
            'link_text' => $link_text,
            'title' => $title,
            'activity_type_key' => $activity_type_key,
            'key' => $key,
            'occurred_at' => (new DateTime($occurred_at))->format(DateTime::ATOM),
            'identity' => [
                'source' => $identity_source,
                $identity_source => $identity_data,
            ]
        ];

        $this->execute($query, 'activities');

        return true;
    }

    private function execute(array $query, string $url): void
    {
        $ch = curl_init();

        $this->prepareCurl($ch, $url, $query);

        curl_exec($ch);
        curl_close($ch);
    }

    private function prepareCurl(&$ch, string $url, array $query): void
    {
        curl_setopt($ch, CURLOPT_URL, self::URL . $this->workplaceId . '/' . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));

    }
}
