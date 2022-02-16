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
use Utopia\Analytics\Adapter\ActiveCampaign;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Event;

class AnalyticsTest extends TestCase
{
    public $ga;

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics("tid=UA-XXXXXXXXX-X,cid=test");
        $this->ac = new ActiveCampaign("key=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx,actid=xxxxxxxxx");
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

    public function testActiveCampaign()
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setName('pageview')
            ->addProp('uid', 'test')
            ->setUrl('https://www.appwrite.io/docs/installation');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent')
            ->setValue('testEvent')
            ->addProp('category', 'testEvent')
            ->addProp('email', 'test@test.com')
            ->setUrl('https://www.appwrite.io/docs/installation');

        $this->assertTrue($this->ac->createEvent($pageviewEvent));
        $this->assertTrue($this->ac->createEvent($normalEvent));

        $this->ac->disable();
        $this->assertFalse($this->ac->createEvent($pageviewEvent));
        $this->assertFalse($this->ac->createEvent($normalEvent));
    }
}