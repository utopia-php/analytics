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

abstract class Analytics
{
    protected $enabled = true;

    /**
     * Enables tracking for this instance.
     */
    public function enable()
    {
        $this->enabled = true;
    }

    /**
     * Disables tracking for this instance.
     */
    public function disable()
    {
        $this->enabled = false;
    }

    /**
     * Generates a hash value based on the hosts name and ip adress. 
     * Can be used to generate a Client ID to identify host machines.
     * 
     * @return string
     */
    static public function getUniqueByHostname():string {
        $host = gethostname();
        return md5(gethostbyname($host).$host);
    }
}
