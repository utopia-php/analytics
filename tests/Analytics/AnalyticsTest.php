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
    /** @var \Utopia\Analytics\Adapter\GoogleAnalytics $ga */
    public $ga;

    /** @var \Utopia\Analytics\Adapter\ActiveCampaign|null $ac */
    public $ac;

    /** @var \Utopia\Analytics\Adapter\Plausible $pa */
    public $pa;

    /** @var \Utopia\Analytics\Adapter\Orbit $orbit */
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
<<<<<<< HEAD
            getenv("AC_KEY"),
            getenv("AC_ACTID"),
            getenv("AC_APIKEY"),
            getenv("AC_ORGID"));
        $this->pa = new Plausible(getenv("PA_DOMAIN"), getenv("PA_APIKEY"),
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36", "192.168.0.1");
        $this->orbit = new Orbit(getenv("OR_WORKSPACEID"), getenv("OR_APIKEY"), "Utopia Testing Suite");
=======
            App::getEnv("AC_KEY"),
            App::getEnv("AC_ACTID"),
            App::getEnv("AC_APIKEY"),
            App::getEnv("AC_ORGID")
        );
        $this->pa = new Plausible(App::getEnv("PA_DOMAIN"), App::getEnv("PA_APIKEY"), "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36", "192.168.0.1");
        $this->orbit = new Orbit(App::getEnv("OR_WORKSPACEID"), App::getEnv("OR_APIKEY"), "Utopia Testing Suite");
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
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
<<<<<<< HEAD
        $normalEvent->setType('myCoolEvent')
            ->setUrl('https://www.appwrite.io/docs/myCoolEvent123')
            ->setProps(['category' => 'coolEvent']);;

         $this->assertTrue($this->pa->createEvent($pageviewEvent));
         $this->assertTrue($this->pa->createEvent($normalEvent));

         $this->pa->disable();
         $this->assertFalse($this->pa->createEvent($pageviewEvent));
         $this->assertFalse($this->pa->createEvent($normalEvent));
    }

    public function testActiveCampaignCreateAccount(): void {
        $this->assertTrue($this->ac->createAccount(
            'Appwrite',
            'appwrite.io',
            1,
            [
                [
                    'customFieldId' => 1,
                    'fieldValue' => 'Hello World!'
                ],
            ]
        ));
    }

    public function testActiveCampaignGetAccount() {
        $accountID = $this->ac->accountExists('Appwrite');
        $this->assertIsNumeric($accountID);

        return [
            'accountID' => $accountID,
        ];
    }

    public function testActiveCampaignCreateContact(): void {
        $this->assertTrue($this->ac->createContact('test@test.com', 'Paul', 'Van Doren'));
=======
        $normalEvent->setType('testEvent')
            ->setName('testEvent'.chr(mt_rand(97, 122)).substr(md5(time()), 1, 5))
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent']);;

        $this->assertTrue($this->pa->send($pageviewEvent));
        $this->assertTrue($this->pa->send($normalEvent));
        $this->assertTrue($this->pa->validate($normalEvent));
    }

    public function testActiveCampaignCreateContact() {
        $this->assertTrue($this->ac->createContact('analytics2@utopiaphp.com', 'Analytics', 'Utopia'));
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
    }

    public function testActiveCampaignGetContact() {
        $contactID = $this->ac->contactExists('analytics2@utopiaphp.com');
        $this->assertIsNumeric($contactID);

        return [
            'contactID' => $contactID
        ];
    }

    public function testActiveCampaignCreateAccount() {
        $this->assertTrue($this->ac->createAccount('Example Account 1', 'https://example.com', '1234567890'));
    }

    /**
     * @depends testActiveCampaignGetContact
     */
<<<<<<< HEAD
    public function testActiveCampaignSyncAsociation($data): void{
=======
    public function testActiveCampaignGetAccount($data) {
        $accountID = $this->ac->accountExists('Example Account 1');
        $this->assertIsNumeric($accountID);

        return array_merge([
            'accountID' => $accountID
        ], $data);
    }

    /**
     * @depends testActiveCampaignGetAccount
     */
    public function testActiveCampaignSyncAsociation($data) {
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Owner'));
        $this->assertTrue($this->ac->syncAssociation($data['accountID'], $data['contactID'], 'Software Developer'));
    }

    /**
     * @depends testActiveCampaignGetContact
     */
<<<<<<< HEAD
    public function testActiveCampaignUpdateContact($data): void {
        $this->assertTrue($this->ac->updateContact($data['contactID'], 'test@test.com', '', '', '7223224241'));
    }

    public function testActiveCampaignDeleteContact(): void {
        $this->assertTrue($this->ac->deleteContact('test@test.com'));
=======
    public function testActiveCampaignUpdateContact($data) {
        $this->assertTrue($this->ac->updateContact($data['contactID'], 'analytics2@utopiaphp.com', '', '', '7223224241'));
    }

    public function testActiveCampaignDeleteContact() {
        $this->assertTrue($this->ac->deleteContact('analytics2@utopiaphp.com'));
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
    }

    /**
     * @depends testActiveCampaignGetAccount
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
     */
    public function testActiveCampaignDeleteAccount($data): void {
        $this->assertTrue($this->ac->deleteAccount($data['accountID']));
    }

<<<<<<< HEAD
    public function testOrbit(): void {
=======
    public function testActiveCampaign() {
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
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
        $event = new Event();
        $event->setType('testEvent')
            ->setName('testEvent')
            ->setUrl('https://www.appwrite.io/docs/installation')
            ->setProps(['category' => 'testEvent', 'email' => 'analytics@utopiaphp.com', 'tags' => ['test', 'test2']]);

        $this->assertTrue($this->orbit->send($event));
        $this->assertTrue($this->orbit->validate($event));
    }
<<<<<<< HEAD
=======

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
>>>>>>> f0306021de06277309d2f121bc1860a639c852b5
}
