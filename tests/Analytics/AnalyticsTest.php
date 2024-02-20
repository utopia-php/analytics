<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Adapter\HubSpot;
use Utopia\Analytics\Adapter\Mixpanel;
use Utopia\Analytics\Adapter\Orbit;
use Utopia\Analytics\Adapter\Plausible;
use Utopia\Analytics\Event;
use Utopia\Http\Http;

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
        $this->ga = new GoogleAnalytics(Http::getEnv('GA_TID') ?? '', Http::getEnv('GA_CID') ?? '');
        $this->pa = new Plausible(Http::getEnv('PA_DOMAIN') ?? '', Http::getEnv('PA_APIKEY') ?? '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36', '192.168.0.1');
        $this->orbit = new Orbit(Http::getEnv('OR_WORKSPACEID') ?? '', Http::getEnv('OR_APIKEY') ?? '', 'Utopia Testing Suite');
        $this->mp = new Mixpanel(Http::getEnv('MP_PROJECT_TOKEN') ?? '');
        $this->hs = new HubSpot(Http::getEnv('HS_APIKEY') ?? '');
    }

    /**
     * @group Plausible
     */
    public function testPlausible()
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setUrl('https://www.appwrite.io/docs/pageview123');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent-'.chr(mt_rand(97, 122)).substr(md5(time()), 1, 5))
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);

        $this->assertTrue($this->pa->send($pageviewEvent));
        $this->assertTrue($this->pa->send($normalEvent));

        sleep(5); // Sometimes it can take a few seconds for Plausible to index the new event
        $this->assertTrue($this->pa->validate($normalEvent));
    }

    /**
     * @group HubSpot
     */
    public function testHubSpotCreateContact()
    {
        $this->assertTrue($this->hs->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));

        sleep(5); // Sometimes it can take a few seconds for HubSpot to index the new contact
    }

    /**
     * @group HubSpot
     *
     * @depends testHubSpotCreateContact
     */
    public function testHubSpotGetContact()
    {
        $tries = 0;

        while ($tries < 5) {
            $contactID = $this->hs->contactExists('analytics2@utopiaphp.com');

            if ($contactID) {
                $this->assertIsNumeric($contactID);
                break;
            }

            var_dump('Waiting for HubSpot to index the new contact... Attempt: '.$tries.'/5');
            sleep(5);
            $tries++;

            if ($tries === 5) {
                $this->fail('HubSpot failed to index the new contact in a reasonable amount of time');
            }
        }

        return [
            'contactID' => $contactID,
        ];
    }

    /**
     * @group HubSpot
     *
     * @depends testHubSpotGetContact
     */
    public function testHubSpotCreateAccount($data)
    {
        $this->assertTrue($this->hs->createAccount('Example Account 1', 'https://example.com', '1234567890'));

        sleep(5); // Sometimes it can take a few seconds for HubSpot to index the new account

        return $data;
    }

    /**
     * @group HubSpot
     *
     * @depends testHubSpotCreateAccount
     */
    public function testHubSpotGetAccount($data)
    {
        $tries = 0;

        while ($tries < 5) {
            $accountID = $this->hs->accountExists('Example Account 1');

            if ($accountID) {
                $this->assertIsNumeric($accountID);
                break;
            }

            var_dump('Waiting for HubSpot to index the new account... Attempt: '.$tries.'/5');
            sleep(5);
            $tries++;

            if ($tries === 5) {
                $this->fail('HubSpot failed to index the new account');
            }
        }

        return array_merge([
            'accountID' => $accountID,
        ], $data);
    }

    /**
     * @depends testHubSpotGetAccount
     *
     * @group HubSpot
     */
    public function testHubSpotSyncAsociation($data)
    {
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));

        return $data;
    }

    /**
     * @depends testHubSpotSyncAsociation
     *
     * @group HubSpot
     */
    public function testHubSpotUpdateContact($data)
    {
        $this->assertTrue($this->hs->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));

        return $data;
    }

    /**
     * @depends testHubSpotUpdateContact
     *
     * @group HubSpot
     */
    public function testHubSpotDeleteContact($data)
    {
        $this->assertTrue($this->hs->deleteContact('analytics2@utopiaphp.com'));

        return $data;
    }

    /**
     * @depends testHubSpotDeleteContact
     *
     * @group HubSpot
     */
    public function testHubSpotUpdateAccount($data)
    {
        $this->assertTrue($this->hs->updateAccount(
            $data['accountID'],
            'Utopia',
            'utopia.com',
            1
        ));

        return $data;
    }

    /**
     * @depends testHubSpotUpdateAccount
     *
     * @group HubSpot
     */
    public function testHubSpotDeleteAccount($data)
    {
        $this->assertTrue($this->hs->deleteAccount($data['accountID']));
    }

    /**
     * @group Orbit
     */
    public function testOrbit(): void
    {
        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2'], 'non-string' => false]);

        $this->assertTrue($this->orbit->send($event));
        $this->assertTrue($this->orbit->validate($event));
    }

    /**
     * @group HubSpot
     */
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

        $this->assertTrue(true);
    }

    /**
     * @group mixpanel
     */
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
