<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\ActiveCampaign;
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

    /** @var \Utopia\Analytics\Adapter\ActiveCampaign|null */
    public $ac;

    /** @var \Utopia\Analytics\Adapter\Plausible */
    public $pa;

    /** @var \Utopia\Analytics\Adapter\Orbit */
    public $orbit;

    /** @var \Utopia\Analytics\Adapter\Mixpanel */
    public $mp;

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics(App::getEnv('GA_TID'), App::getEnv('GA_CID'));
        $this->ac = new ActiveCampaign(
            App::getEnv('AC_KEY'),
            App::getEnv('AC_ACTID'),
            App::getEnv('AC_APIKEY'),
            App::getEnv('AC_ORGID')
        );
        $this->pa = new Plausible(App::getEnv('PA_DOMAIN'), App::getEnv('PA_APIKEY'), 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36', '192.168.0.1');
        $this->orbit = new Orbit(App::getEnv('OR_WORKSPACEID'), App::getEnv('OR_APIKEY'), 'Utopia Testing Suite');
        $this->mp = new Mixpanel(App::getEnv('MP_PROJECT_TOKEN'));
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

    public function testActiveCampaignCreateContact()
    {
        $this->assertTrue($this->ac->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));
    }

    public function testActiveCampaignGetContact()
    {
        $contactID = $this->ac->contactExists('analytics2@utopiaphp.com');
        $this->assertIsNumeric($contactID);

        return [
            'contactID' => $contactID,
        ];
    }

    public function testActiveCampaignCreateAccount()
    {
        $this->assertTrue($this->ac->createAccount('Example Account 1', 'https://example.com', '1234567890'));
    }

    /**
     * @depends testActiveCampaignGetContact
     */
    public function testActiveCampaignGetAccount($data)
    {
        $accountID = $this->ac->accountExists('Example Account 1');
        $this->assertIsNumeric($accountID);

        return array_merge([
            'accountID' => $accountID,
        ], $data);
    }

    /**
     * @depends testActiveCampaignGetAccount
     */
    public function testActiveCampaignSyncAsociation($data)
    {
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));
    }

    /**
     * @depends testActiveCampaignGetContact
     */
    public function testActiveCampaignUpdateContact($data)
    {
        $this->assertTrue($this->ac->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));
    }

    public function testActiveCampaignDeleteContact()
    {
        $this->assertTrue($this->ac->deleteContact('analytics2@utopiaphp.com'));
    }

    /**
     * @depends testActiveCampaignGetAccount
     */
    public function testActiveCampaignUpdateAccount($data)
    {
        $this->assertTrue($this->ac->updateAccount(
            $data['accountID'],
            'Utopia',
            'utopia.com',
            1));
    }

    /**
     * @depends testActiveCampaignGetAccount
     */
    public function testActiveCampaignDeleteAccount($data)
    {
        $this->assertTrue($this->ac->deleteAccount($data['accountID']));
    }

    public function testActiveCampaign()
    {
        $this->assertTrue($this->ac->createContact('analytics@utopiaphp.com', 'Analytics', 'Utopia'));

        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(time()), 1, 5))
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'analytics:test', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2']]);

        $this->assertTrue($this->ac->send($event));
        sleep(10);
        $this->assertTrue($this->ac->validate($event));
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
        if ($this->ac->contactExists('analytics@utopiaphp.com')) {
            $this->assertTrue($this->ac->deleteContact('analytics@utopiaphp.com'));
        }

        if ($this->ac->contactExists('analytics2@utopiaphp.com')) {
            $this->assertTrue($this->ac->deleteContact('analytics2@utopiaphp.com'));
        }

        if ($this->ac->accountExists('Example Account 1')) {
            $this->assertTrue($this->ac->deleteAccount($this->ac->accountExists('Example Account 1')));
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
                'custom_prop6' => []
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
