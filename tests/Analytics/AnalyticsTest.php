<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\ActiveCampaign;
use Utopia\Analytics\Adapter\GoogleAnalytics;
use Utopia\Analytics\Adapter\Orbit;
use Utopia\Analytics\Adapter\Plausible;
use Utopia\Analytics\Event;
use Utopia\App;

class AnalyticsTest extends TestCase
{
    /** @var GoogleAnalytics $ga */
    public $ga;

    /** @var ActiveCampaign|null $ac */
    public $ac;

    /** @var Plausible $pa */
    public $pa;

    /** @var Orbit $orbit */
    public $orbit;
    
    public function __construct()
    {
        parent::__construct();
        $this->ga = new GoogleAnalytics(App::getEnv("GA_TID"), App::getEnv("GA_CID"));
        $this->ac = new ActiveCampaign(
            App::getEnv("AC_KEY"),
            App::getEnv("AC_ACTID"),
            App::getEnv("AC_APIKEY"),
            App::getEnv("AC_ORGID")
        );
        $this->pa = new Plausible(App::getEnv("PA_DOMAIN"), App::getEnv("PA_APIKEY"), "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36", "192.168.0.1");
        $this->orbit = new Orbit(App::getEnv("OR_WORKSPACEID"), App::getEnv("OR_APIKEY"), "Utopia Testing Suite");
    }

    public function setUp(): void
    {
        $this->ga = new GoogleAnalytics(App::getEnv("GA_TID"), App::getEnv("GA_CID"));
        $this->ac = new ActiveCampaign(
            App::getEnv("AC_KEY"),
            App::getEnv("AC_ACTID"),
            App::getEnv("AC_APIKEY"),
            App::getEnv("AC_ORGID")
        );
        $this->pa = new Plausible(App::getEnv("PA_DOMAIN"), App::getEnv("PA_APIKEY"), "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36", "192.168.0.1");
        $this->orbit = new Orbit(App::getEnv("OR_WORKSPACEID"), App::getEnv("OR_APIKEY"), "Utopia Testing Suite");
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
            ->setProps(['category' => 'testEvent']);;

        $this->assertTrue($this->ga->validate($pageviewEvent));
        $this->assertTrue($this->ga->validate($normalEvent));

        $this->ga->disable();
        $this->assertFalse($this->ga->validate($pageviewEvent));
        $this->assertFalse($this->ga->validate($normalEvent));
    }

    public function testPlausible(): void
    {
        $pageviewEvent = new Event();
        $pageviewEvent
            ->setType('pageview')
            ->setUrl('https://www.appwrite.io/docs/pageview123');

        $normalEvent = new Event();
        $normalEvent->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(strval(time())), 1, 5))
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);;

        $this->assertTrue($this->pa->send($pageviewEvent));
        $this->assertTrue($this->pa->send($normalEvent));
        $this->assertTrue($this->pa->validate($normalEvent));
    }

    public function testActiveCampaignCreateContact(): void 
    {
        $this->assertTrue($this->ac->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));
    }

    /**
     * @return array<string, int>
     */
    public function testActiveCampaignGetContact(): array
    {
        $contactID = $this->ac->contactExists('analytics2@utopiaphp.com');
        $this->assertIsNumeric($contactID);

        return [
            'contactID' => $contactID
        ];
    }

    public function testActiveCampaignCreateAccount(): void 
    {
        $this->assertTrue($this->ac->createAccount('Example Account 1', 'https://example.com', 1234567890));
    }

    /**
     * @depends testActiveCampaignGetContact
     * 
     * @param array<string,string> $data
     * 
     * @return array<string, mixed>
     */
    public function testActiveCampaignGetAccount($data): array 
    {
        $accountID = $this->ac->accountExists('Example Account 1');
        $this->assertIsNumeric($accountID);

        return array_merge([
            'accountID' => $accountID
        ], $data);
    }

    /**
     * @depends testActiveCampaignGetAccount
     * 
     * @param array<string, mixed> $data
     */
    public function testActiveCampaignSyncAsociation($data): void 
    {
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));
    }

    /**
     * @depends testActiveCampaignGetContact
     * 
     * @param array<string, mixed> $data
     */
    public function testActiveCampaignUpdateContact($data): void 
    {
        $this->assertTrue($this->ac->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));
    }

    public function testActiveCampaignDeleteContact(): void 
    {
        $this->assertTrue($this->ac->deleteContact('analytics2@utopiaphp.com'));
    }

    /**
     * @depends testActiveCampaignGetAccount
     * 
     * @param array<string, mixed> $data
     */
    public function testActiveCampaignUpdateAccount($data): void {
        $this->assertTrue($this->ac->updateAccount(
            $data['accountID'],
            'Utopia',
            'utopia.com',
            1));
    }

    /**
     * @depends testActiveCampaignGetAccount
     * 
     * @param array<string, mixed> $data
     */
    public function testActiveCampaignDeleteAccount($data): void 
    {
        $this->assertTrue($this->ac->deleteAccount($data['accountID']));
    }

    public function testActiveCampaign(): void 
    {
        $this->assertTrue($this->ac->createContact('analytics@utopiaphp.com', 'Analytics', 'Utopia'));

        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(strval(time())), 1, 5))
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

    function testCleanup(): void
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
}
