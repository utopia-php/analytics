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
    protected bool $enabled = true;

    /**
     * Gets the name of the adapter.
     * 
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Adapter constructor.
     * 
     * @param string $configuration
     * 
     * @return Adapter
     */
    abstract public function __construct(string $configuration);

    /**
     * Enables tracking for this instance.
     * 
     * @return void
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disables tracking for this instance.
     * 
     * @return void
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Creates an Event on the remote analytics platform.
     * 
     * @param Event $event
     * @return bool
     */
    abstract public function createEvent(Event $event): bool;

}