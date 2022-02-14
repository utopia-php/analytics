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

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Adapter\Plausible;
use Utopia\Analytics\Event;

class AnalyticsTest extends TestCase
{
    public $ga;

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics("UA-XXXXXXXXX-X", "test");
        $this->pa = new Plausible("testdomain", "UA-XXXXXXXXX-X", "test");
    }

    public function testGoogleAnalytics()
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setName('pageview')
            ->setUrl('https://www.appwrite.io/docs/installation');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent')
            ->setValue('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);;

        $this->assertTrue($this->ga->createEvent($pageviewEvent));
        $this->assertTrue($this->ga->createEvent($normalEvent));

        $this->ga->disable();
        $this->assertFalse($this->ga->createEvent($pageviewEvent));
        $this->assertFalse($this->ga->createEvent($normalEvent));
    }

    public function testPlausible()
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setName('pageview')
            ->setUrl('https://www.appwrite.io/docs/installation');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent')
            ->setValue('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);;

        $this->assertTrue($this->pa->createEvent($pageviewEvent));
        $this->assertTrue($this->pa->createEvent($normalEvent));

        $this->pa->disable();
        $this->assertFalse($this->pa->createEvent($pageviewEvent));
        $this->assertFalse($this->pa->createEvent($normalEvent));
    }
}