<?php

namespace Utopia\Analytics\Adapter;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Utopia\Analytics\Adapter;
use Utopia\Analytics\Event;

class ClickHouse extends Adapter
{
    /**
     * ClickHouse HTTP endpoint (including scheme and port).
     */
    protected string $endpoint;

    /**
     * Target database.
     */
    protected string $database;

    /**
     * Target table.
     */
    protected string $table;

    /**
     * Username used for authentication.
     */
    protected string $username;

    /**
     * Password used for authentication.
     */
    protected string $password;

    /**
     * Whether the schema has been created for this instance.
     */
    private bool $schemaEnsured = false;

    /**
     * @return ClickHouse
     */
    public function __construct(
        string $endpoint,
        string $database = 'analytics',
        string $table = 'events',
        string $username = 'default',
        string $password = ''
    ) {
        $this->endpoint = rtrim($endpoint, '/');
        $this->guardIdentifier($database);
        $this->guardIdentifier($table);
        $this->database = $database;
        $this->table = $table;
        $this->username = $username;
        $this->password = $password;
        $this->clientIP = '';
    }

    /**
     * Gets the name of the adapter.
     */
    public function getName(): string
    {
        return 'ClickHouse';
    }

    /**
     * Creates an Event on the remote analytics platform.
     */
    public function send(Event|array $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $events = is_array($event) ? $event : [$event];

        $rows = [];
        foreach ($events as $singleEvent) {
            $this->assertEventIsValid($singleEvent);

            [$dimensions, $meta] = $this->buildDimensions($singleEvent);

            $rows[] = array_merge($dimensions, [
                'metaKey' => $meta['keys'],
                'metaValue' => $meta['values'],
                'userAgent' => $this->userAgent ?? '',
                'clientIp' => $this->clientIP ?? '',
                'createdAt' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
            ]);
        }

        if (empty($rows)) {
            return false;
        }

        $insertSQL = sprintf(
            'INSERT INTO %s.%s (
                eventType,
                eventName,
                url,
                hostName,
                pathName,
                referrer,
                referrerSource,
                countryCode,
                screenSize,
                operatingSystem,
                operatingSystemVersion,
                browser,
                browserVersion,
                utmMedium,
                utmSource,
                utmCampaign,
                utmContent,
                utmTerm,
                revenueReportingAmount,
                revenueReportingCurrency,
                revenueSourceAmount,
                revenueSourceCurrency,
                metaKey,
                metaValue,
                userAgent,
                clientIp,
                createdAt
            ) FORMAT JSONEachRow',
            $this->database,
            $this->table
        );

        $this->execute($insertSQL, [], $rows);

        return true;
    }

    /**
     * Validate the adapter by checking connectivity and ensuring the event was stored.
     */
    public function validate(Event $event): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $this->assertEventIsValid($event);

        $selectSQL = sprintf(
            'SELECT count() AS cnt FROM %s.%s WHERE eventType = {eventType:String} AND eventName = {eventName:String} AND url = {url:String} FORMAT JSON',
            $this->database,
            $this->table
        );

        $response = $this->execute($selectSQL, [
            'eventType' => $event->getType(),
            'eventName' => $event->getName(),
            'url' => $event->getUrl(),
        ], null, true);

        return ($response['data'][0]['cnt'] ?? 0) > 0;
    }

    /**
     * Drops and recreates the schema.
     * Intended for test environments where a clean slate is required.
     */
    public function resetStorage(): void
    {
        $this->execute(sprintf('DROP TABLE IF EXISTS %s.%s', $this->database, $this->table));
        $this->schemaEnsured = false;
        $this->setup();
    }

    /**
     * Ensure table exists using Plausible-inspired MergeTree settings.
     */
    public function setup(): void
    {
        if ($this->schemaEnsured) {
            return;
        }

        // ClickHouse schema tuned for large volumes
        // - LowCardinality on many string dimensions reduces dictionary size.
        // - ZSTD(3) balances compression and CPU.
        // - Delta/LZ4 on timestamps for efficient monotonic time storage.
        // - Partition by month keeps merges bounded and pruning fast.
        // - ORDER BY adds toDate(createdAt) to align with partitioning and common filters.
        // - index_granularity=8192 is the ClickHouse default; kept explicit for clarity when tuning.
        $createTableSQL = sprintf(
            'CREATE TABLE IF NOT EXISTS %s.%s (
                eventType LowCardinality(String) CODEC(ZSTD(3)),
                eventName String CODEC(ZSTD(3)),
                url String CODEC(ZSTD(3)),
                hostName LowCardinality(String) CODEC(ZSTD(3)),
                pathName String CODEC(ZSTD(3)),
                referrer String CODEC(ZSTD(3)),
                referrerSource String CODEC(ZSTD(3)),
                countryCode FixedString(2),
                screenSize LowCardinality(String) CODEC(ZSTD(3)),
                operatingSystem LowCardinality(String) CODEC(ZSTD(3)),
                operatingSystemVersion LowCardinality(String) CODEC(ZSTD(3)),
                browser LowCardinality(String) CODEC(ZSTD(3)),
                browserVersion LowCardinality(String) CODEC(ZSTD(3)),
                utmMedium String CODEC(ZSTD(3)),
                utmSource String CODEC(ZSTD(3)),
                utmCampaign String CODEC(ZSTD(3)),
                utmContent String CODEC(ZSTD(3)),
                utmTerm String CODEC(ZSTD(3)),
                revenueReportingAmount Nullable(Decimal(18, 3)),
                revenueReportingCurrency FixedString(3),
                revenueSourceAmount Nullable(Decimal(18, 3)),
                revenueSourceCurrency FixedString(3),
                metaKey Array(String) CODEC(ZSTD(3)),
                metaValue Array(String) CODEC(ZSTD(3)),
                userAgent String CODEC(ZSTD(3)),
                clientIp String CODEC(ZSTD(3)),
                createdAt DateTime CODEC(Delta(4), LZ4)
            ) ENGINE = MergeTree()
            PARTITION BY toYYYYMM(createdAt)
            ORDER BY (eventType, toDate(createdAt), hostName, pathName, createdAt)
            SETTINGS index_granularity = 8192',
            $this->database,
            $this->table
        );

        $this->execute($createTableSQL);
        $this->schemaEnsured = true;
    }

    /**
     * Execute an SQL statement against ClickHouse.
     *
     * @param  string  $sql  The SQL query to execute.
     * @param  array  $params  Named parameters passed as param_* query params.
     * @param  array|null  $rows  JSONEachRow payload (each item is encoded as JSON).
     * @param  bool  $expectJson  Whether to decode JSON response.
     *
     * @throws Exception
     */
    private function execute(string $sql, array $params = [], ?array $rows = null, bool $expectJson = false): array|string
    {
        $paramPayload = [];

        foreach ($params as $key => $value) {
            $paramPayload['param_'.$key] = $value;
        }

        $queryParams = array_merge([
            'database' => $this->database,
            'user' => $this->username,
        ], $paramPayload);

        if (! empty($this->password)) {
            $queryParams['password'] = $this->password;
        }

        $url = $this->endpoint.'/?'.http_build_query($queryParams);

        $body = $sql;
        $headers = ['Content-Type: text/plain'];

        if (! is_null($rows)) {
            $jsonLines = array_map(
                fn ($row) => json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
                $rows
            );
            $body .= "\n".implode("\n", $jsonLines);
            $headers = ['Content-Type: application/json'];
        }

        $ch = curl_init($url);
        if (! $ch instanceof \CurlHandle) {
            throw new Exception('Failed to initialize cURL handle');
        }
        $responseHeaders = [];

        try {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
                $len = strlen($header);
                $header = explode(':', strtolower($header), 2);

                if (count($header) < 2) {
                    return $len;
                }

                $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);

                return $len;
            });

            $responseBody = curl_exec($ch);
            $responseStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch), $responseStatus);
            }

            if ($responseStatus >= 400) {
                throw new Exception($responseBody ?: 'ClickHouse responded with error', $responseStatus);
            }

            $contentType = $responseHeaders['content-type'] ?? '';

            if ($expectJson || str_starts_with($contentType, 'application/json')) {
                $decoded = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Failed to parse ClickHouse JSON response: '.json_last_error_msg());
                }

                return $decoded;
            }

            return $responseBody;
        } finally {
            $this->closeHandle($ch);
        }
    }

    /**
     * Ensures basic event sanity before touching ClickHouse.
     */
    private function assertEventIsValid(Event $event): void
    {
        if (empty($event->getType())) {
            throw new Exception('Event type is required');
        }

        if (empty($event->getUrl())) {
            throw new Exception('Event URL is required');
        }

        if (empty($event->getName())) {
            throw new Exception('Event name is required');
        }
    }

    /**
     * Close a curl handle without triggering deprecation warnings on CurlHandle objects.
     */
    private function closeHandle(\CurlHandle $ch): void
    {
        // Unset releases the handle; avoids deprecated curl_close on CurlHandle.
        unset($ch);
    }

    /**
     * Map incoming event into structured dimensions and meta arrays.
     */
    private function buildDimensions(Event $event): array
    {
        $urlParts = parse_url($event->getUrl());
        $hostname = $urlParts['host'] ?? '';
        $pathname = ($urlParts['path'] ?? '/').(isset($urlParts['query']) ? '?'.$urlParts['query'] : '');

        $props = $event->getProps();

        $dimensions = [
            'eventType' => $event->getType(),
            'eventName' => $event->getName(),
            'url' => $event->getUrl(),
            'hostName' => $hostname,
            'pathName' => $pathname,
            'referrer' => $props['referrer'] ?? '',
            'referrerSource' => $props['referrer_source'] ?? '',
            'countryCode' => $props['country_code'] ?? '',
            'screenSize' => $props['screen_size'] ?? '',
            'operatingSystem' => $props['operating_system'] ?? '',
            'operatingSystemVersion' => $props['operating_system_version'] ?? '',
            'browser' => $props['browser'] ?? '',
            'browserVersion' => $props['browser_version'] ?? '',
            'utmMedium' => $props['utm_medium'] ?? '',
            'utmSource' => $props['utm_source'] ?? '',
            'utmCampaign' => $props['utm_campaign'] ?? '',
            'utmContent' => $props['utm_content'] ?? '',
            'utmTerm' => $props['utm_term'] ?? '',
            'revenueReportingAmount' => $props['revenue_reporting_amount'] ?? null,
            'revenueReportingCurrency' => $props['revenue_reporting_currency'] ?? '',
            'revenueSourceAmount' => $props['revenue_source_amount'] ?? null,
            'revenueSourceCurrency' => $props['revenue_source_currency'] ?? '',
        ];

        $reservedKeys = array_keys($dimensions);

        $metaKeys = [];
        $metaValues = [];

        foreach ($props as $key => $value) {
            if (in_array($key, $reservedKeys, true)) {
                continue;
            }

            $metaKeys[] = (string) $key;
            $metaValues[] = $this->stringifyMetaValue($value);
        }

        return [$dimensions, ['keys' => $metaKeys, 'values' => $metaValues]];
    }

    /**
     * Convert meta value to string for ClickHouse Array(String) columns.
     */
    private function stringifyMetaValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return $value === null ? '' : (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Guard against SQL injection via identifiers.
     *
     * ClickHouse is case-sensitive and identifiers should be alphanumeric with underscores.
     */
    private function guardIdentifier(string $identifier): void
    {
        if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new Exception('Invalid ClickHouse identifier: '.$identifier);
        }
    }
}
