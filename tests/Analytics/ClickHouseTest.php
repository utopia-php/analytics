<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Analytics\Adapter\ClickHouse;
use Utopia\Analytics\Event;
use Utopia\System\System;

class ClickHouseTest extends TestCase
{
    private ClickHouse $clickhouse;

    protected function setUp(): void
    {
        $endpoint = System::getEnv('CLICKHOUSE_ENDPOINT') ?? 'http://127.0.0.1:8123';
        $database = System::getEnv('CLICKHOUSE_DATABASE') ?? 'analytics';
        $table = System::getEnv('CLICKHOUSE_TABLE') ?? 'events';
        $user = System::getEnv('CLICKHOUSE_USER') ?? 'default';
        $password = System::getEnv('CLICKHOUSE_PASSWORD') ?? '';

        $this->clickhouse = new ClickHouse($endpoint, $database, $table, $user, $password);
        $this->clickhouse->setClientIP('127.0.0.1');
        $this->clickhouse->setUserAgent('Utopia Test Suite');
        //$this->clickhouse->resetStorage();
    }

    /**
     * @group ClickHouse
     */
    public function test_clickhouse_persists_events(): void
    {
        $event = (new Event)
            ->setType('clickhouse-test-'.uniqid('', true))
            ->setName('clickhouse-e2e')
            ->setUrl('https://example.com/path?utm=analytics')
            ->setProps([
                'plan' => 'pro',
                'tags' => ['php', 'analytics'],
                'properties' => ['nested' => ['key' => 'value']],
            ]);

        $this->assertTrue($this->clickhouse->send($event));
        $this->assertTrue($this->clickhouse->validate($event));
    }

    /**
     * @group ClickHouse
     */
    public function test_clickhouse_batch_events(): void
    {
        $events = [];

        for ($i = 0; $i < 2; $i++) {
            $events[] = (new Event)
                ->setType('clickhouse-batch-'.uniqid('', true))
                ->setName('clickhouse-e2e-batch')
                ->setUrl('https://example.com/path?utm=batch'.$i)
                ->setProps([
                    'plan' => 'pro',
                    'index' => $i,
                ]);
        }

        $this->assertTrue($this->clickhouse->send($events));
        $this->assertTrue($this->clickhouse->validate($events[0]));
        $this->assertTrue($this->clickhouse->validate($events[1]));
    }

    /**
     * @group ClickHouse
     */
    public function test_clickhouse_validate_requires_minimum_fields(): void
    {
        $event = new Event;
        $this->expectException(\Exception::class);
        $this->clickhouse->validate($event);
    }
}
