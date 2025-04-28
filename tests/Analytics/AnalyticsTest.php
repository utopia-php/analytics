<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Adapter\HubSpot;
use Utopia\Analytics\Adapter\Mixpanel;
use Utopia\Analytics\Adapter\Orbit;
use Utopia\Analytics\Adapter\Plausible;
use Utopia\Analytics\Adapter\ReoDev;
use Utopia\Analytics\Event;
use Utopia\System\System;

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

    /** @var \Utopia\Analytics\Adapter\ReoDev */
    public $reodev;

    protected function setUp(): void
    {
        $this->ga = new GoogleAnalytics(System::getEnv('GA_TID') ?? '', System::getEnv('GA_CID') ?? '');
        $this->pa = new Plausible(System::getEnv('PA_DOMAIN') ?? '', System::getEnv('PA_APIKEY') ?? '', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36', '192.168.0.1');
        $this->orbit = new Orbit(System::getEnv('OR_WORKSPACEID') ?? '', System::getEnv('OR_APIKEY') ?? '', 'Utopia Testing Suite');
        $this->mp = new Mixpanel(System::getEnv('MP_PROJECT_TOKEN') ?? '');
        $this->hs = new HubSpot(System::getEnv('HS_APIKEY') ?? '');
        $this->reodev = new ReoDev(System::getEnv('REO_APIKEY') ?? '');
    }

    /**
     * @group Plausible
     */
    public function test_plausible()
    {
        $pageviewEvent = new Event;
        $pageviewEvent
            ->setType('pageview')
            ->setUrl('https://www.appwrite.io/docs/pageview123');

        $normalEvent = new Event;
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
    public function test_hub_spot_create_contact()
    {
        $this->assertTrue($this->hs->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));

        sleep(5); // Sometimes it can take a few seconds for HubSpot to index the new contact
    }

    /**
     * @group HubSpot
     *
     * @depends test_hub_spot_create_contact
     */
    public function test_hub_spot_get_contact()
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
     * @depends test_hub_spot_get_contact
     */
    public function test_hub_spot_create_account($data)
    {
        $this->assertTrue($this->hs->createAccount('Example Account 1', 'https://example.com', '1234567890'));

        sleep(5); // Sometimes it can take a few seconds for HubSpot to index the new account

        return $data;
    }

    /**
     * @group HubSpot
     *
     * @depends test_hub_spot_create_account
     */
    public function test_hub_spot_get_account($data)
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
     * @depends test_hub_spot_get_account
     *
     * @group HubSpot
     */
    public function test_hub_spot_sync_asociation($data)
    {
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->hs->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));

        return $data;
    }

    /**
     * @depends test_hub_spot_sync_asociation
     *
     * @group HubSpot
     */
    public function test_hub_spot_update_contact($data)
    {
        $this->assertTrue($this->hs->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));

        return $data;
    }

    /**
     * @depends test_hub_spot_update_contact
     *
     * @group HubSpot
     */
    public function test_hub_spot_delete_contact($data)
    {
        $this->assertTrue($this->hs->deleteContact('analytics2@utopiaphp.com'));

        return $data;
    }

    /**
     * @depends test_hub_spot_delete_contact
     *
     * @group HubSpot
     */
    public function test_hub_spot_update_account($data)
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
     * @depends test_hub_spot_update_account
     *
     * @group HubSpot
     */
    public function test_hub_spot_delete_account($data)
    {
        $this->assertTrue($this->hs->deleteAccount($data['accountID']));
    }

    /**
     * @group Orbit
     */
    public function test_orbit(): void
    {
        $event = new Event;
        $event->setType('testEvent')
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2'], 'non_string' => false]);

        $this->assertTrue($this->orbit->send($event));
        $this->assertTrue($this->orbit->validate($event));
    }

    /**
     * @group HubSpot
     */
    public function test_cleanup(): void
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
     * @group Mixpanel
     */
    public function test_mixpanel()
    {
        /** Create a simple track event */
        $event = new Event;
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

    /**
     * @group ReoDev
     */
    public function test_reo_dev()
    {
        $this->reodev
            ->setClientIP('127.0.0.1')
            ->setUserAgent('Utopia Test Suite');

        // Test successful event with all required fields and no filter
        $event = new Event;
        $event
            ->setName('appwrite_docs')
            ->setType('button_click')
            ->setUrl('appwrite.io/docs')
            ->setProps([
                'email' => 'developer@utopiaphp.com',
                'name' => 'Test Developer',
                'account' => 'cloud',
                'environment' => 'DEVELOPMENT',
                'custom_prop1' => 'value1',
                'custom_prop2' => 'value2',
            ]);

        $this->assertTrue($this->reodev->validate($event));
        $this->assertTrue($this->reodev->send($event));

        // Test event without email (should fail validation and send)
        $invalidEvent = new Event;
        $invalidEvent
            ->setName('appwrite_docs')
            ->setType('page_view') // Use a different type for clarity
            ->setUrl('appwrite.io/docs')
            ->setProps([
                'name' => 'Test Developer',
                'account' => 'cloud',
                'environment' => 'DEVELOPMENT',
            ]);

        $this->assertFalse($this->reodev->validate($invalidEvent));
        $this->assertFalse($this->reodev->send($invalidEvent));

        // Test event type filtering
        $allowedTypes = ['submit_account_login'];
        $this->reodev->setAllowedEventTypes($allowedTypes);

        // Disallowed event
        $disallowedEvent = new Event;
        $disallowedEvent
            ->setName('signup')
            ->setType('submit_signup')
            ->setUrl('appwrite.io/signup')
            ->setProps([
                'email' => 'dev3@utopiaphp.com',
                'account' => 'cloud',
                'environment' => 'DEVELOPMENT',
            ]);

        $this->assertFalse($this->reodev->validate($disallowedEvent));
        $this->assertFalse($this->reodev->send($disallowedEvent));
    }
}
