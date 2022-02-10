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

abstract class Adapter
{
    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Enables tracking for this instance.
     * 
     * @return void
     */
    abstract public function enable(): void;

    /**
     * Disables tracking for this instance.
     * 
     * @return void
     */
    abstract public function disable(): void;

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    abstract public function createEvent(Event $event): bool;

}