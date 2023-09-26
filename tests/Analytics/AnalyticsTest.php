<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\HubSpot;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Adapter\Mixpanel;
use Utopia\Analytics\Adapter\Orbit;
use Utopia\Analytics\Adapter\Plausible;
use Utopia\Analytics\Event;
use Utopia\App;

class AnalyticsTest extends TestCase
{
    /** @var \Utopia\Analytics\Adapter\GoogleAnalytics */
    public $ga;

    /** @var \Utopia\Analytics\Adapter\Plausible */
    public $pa;

    /** @var \Utopia\Analytics\Adapter\Orbit */
    public $orbit;

    /** @var \Utopia\Analytics\Adapter\Mixpanel */
    public $mp;

    /** @var \Utopia\Analytics\Adapter\HubSpot */
    public $hs;

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics(App::getEnv('GA_TID'), App::getEnv('GA_CID'));
        $this->pa = new Plausible(App::getEnv('PA_DOMAIN'), App::getEnv('PA_APIKEY'), 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36', '192.168.0.1');
        $this->orbit = new Orbit(App::getEnv('OR_WORKSPACEID'), App::getEnv('OR_APIKEY'), 'Utopia Testing Suite');
        $this->mp = new Mixpanel(App::getEnv('MP_PROJECT_TOKEN'));
        $this->hs = new HubSpot(App::getEnv('HS_APIKEY'));
    }

    public function testGoogleAnalytics(): void
    {
        // Use Measurement Protocol Validation Server for testing.
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setName('pageview')
            ->setUrl('https://www.appwrite.io/docs/installation');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);

        $this->assertTrue($this->ga->validate($pageviewEvent));
        $this->assertTrue($this->ga->validate($normalEvent));

        $this->ga->disable();
        $this->assertFalse($this->ga->validate($pageviewEvent));
        $this->assertFalse($this->ga->validate($normalEvent));
    }

    public function testPlausible()
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setUrl('https://www.appwrite.io/docs/pageview123');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(time()), 1, 5))
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);

        $this->assertTrue($this->pa->send($pageviewEvent));
        $this->assertTrue($this->pa->send($normalEvent));
        $this->assertTrue($this->pa->validate($normalEvent));
    }

    public function testHubSpotCreateContact()
    {
        $this->assertTrue($this->hs->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));
    }

    public function testHubSpotGetContact()
    {
        $contactID = $this->hs->contactExists('analytics2@utopiaphp.com');
        $this->assertIsNumeric($contactID);

        return [
            'contactID' => $contactID,
        ];
    }

    public function testHubSpotCreateAccount()
    {
        $this->assertTrue($this->hs->createAccount('Example Account 1', 'https://example.com', '1234567890'));
    }

    /**
     * @depends testHubSpotGetContact
     */
    public function testHubSpotGetAccount($data)
    {
        $accountID = $this->hs->accountExists('Example Account 1');
        $this->assertIsNumeric($accountID);

        return array_merge([
            'accountID' => $accountID,
        ], $data);
    }

    /**
     * @depends testHubSpotGetAccount
     */
    public function testHubSpotSyncAsociation($data)
    {
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));
    }

    /**
     * @depends testHubSpotGetContact
     */
    public function testHubSpotUpdateContact($data)
    {
        $this->assertTrue($this->hs->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));
    }

    public function testHubSpotDeleteContact()
    {
        $this->assertTrue($this->hs->deleteContact('analytics2@utopiaphp.com'));
    }

    /**
     * @depends testHubSpotGetAccount
     */
    public function testHubSpotUpdateAccount($data)
    {
        $this->assertTrue($this->hs->updateAccount(
            $data['accountID'],
            'Utopia',
            'utopia.com',
            1));
    }

    /**
     * @depends testHubSpotGetAccount
     */
    public function testHubSpotDeleteAccount($data)
    {
        $this->assertTrue($this->hs->deleteAccount($data['accountID']));
    }

    public function testHubSpot()
    {
        $this->assertTrue($this->hs->createContact('analytics@utopiaphp.com', 'Analytics', 'Utopia'));

        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(time()), 1, 5))
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'analytics:test', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2']]);

        $this->assertTrue($this->hs->send($event));
        sleep(10);
        $this->assertTrue($this->hs->validate($event));
    }

    public function testOrbit(): void
    {
        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2']]);

        $this->assertTrue($this->orbit->send($event));
        $this->assertTrue($this->orbit->validate($event));
    }

    public function testCleanup(): void
    {
        if ($this->hs->contactExists('analytics@utopiaphp.com')) {
            $this->assertTrue($this->hs->deleteContact('analytics@utopiaphp.com'));
        }

        if ($this->hs->contactExists('analytics2@utopiaphp.com')) {
            $this->assertTrue($this->hs->deleteContact('analytics2@utopiaphp.com'));
        }

        if ($this->hs->accountExists('Example Account 1')) {
            $this->assertTrue($this->hs->deleteAccount($this->hs->accountExists('Example Account 1')));
        }
    }

    public function testMixpanel()
    {
        /** Create a simple track event */
        $event = new Event();
        $event
            ->setName('testEvent')
            ->setType('click')
            ->setUrl('https://utopia-php.com/docs/installation')
            ->setProps([
                'time' => time(),
                'email' => 'analytics@utopiaphp.com',
                'custom_prop1' => 'custom_value1',
                'custom_prop2' => 'custom_value2',
                'custom_prop3' => 'custom_value3',
                'custom_prop4' => '',
                'custom_prop5' => null,
                'custom_prop6' => [],
            ]);

        $this->assertTrue($this->mp->send($event));

        /** Create a user profile */
        $res = $this->mp->createProfile('analytics@utopiaphp.com', '132.154.23.14', [
            'email' => 'analytics@utopiaphp.com',
            'name' => 'Utopia Analytics',
            'tags' => ['tag1', 'tag2'],
            'union_field' => ['value1'],
        ]);
        $this->assertTrue($res);

        /** Append properties to the user profile */
        $res = $this->mp->appendProperties('analytics@utopiaphp.com', ['union_field' => ['value2', 'value3']]);
        $this->assertTrue($res);
    }
}
