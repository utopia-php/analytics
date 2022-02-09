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
class AnalyticsTest extends TestCase
{
    public $ga;
    public $plausible;

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics("UA-132779463-1", "test");
    }

    public function testGoogleAnalytics()
    {
        $this->assertTrue($this->ga->createPageView("appwrite.io", "/docs/installation"));
        $this->assertTrue($this->ga->createEvent("testEvent", "testEvent"));

        $this->ga->disable();
        $this->assertFalse($this->ga->createPageView("appwrite.io", "/docs/installation"));
        $this->assertFalse($this->ga->createEvent("testEvent", "testEvent"));
    }
}
