<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Reads reports from the GA4 Data API using a service account.
 *
 * Talks to Google's REST endpoints directly: a signed JWT is exchanged for
 * an access token, which then authorises the report calls. No SDK needed.
 */
class GoogleAnalytics
{
    /** Date ranges the dashboard offers, in days. */
    public const RANGES = [7, 28, 90];

    /** Custom events the public site sends, and how the dashboard names them. */
    public const EVENTS = [
        'resume_download' => 'Résumé PDF downloads',
        'contact_card_download' => 'Contact card saves',
        'project_preview' => 'Project previews opened',
        'contact_message' => 'Contact messages sent',
    ];

    private const SCOPE = 'https://www.googleapis.com/auth/analytics.readonly';

    public function __construct(private ?string $propertyId, private ?array $credentials) {}

    public static function fromSettings(): static
    {
        return new static(
            Setting::read('ga_property_id'),
            json_decode((string) Setting::read('ga_credentials'), true) ?: null,
        );
    }

    public function isConnected(): bool
    {
        return filled($this->propertyId) && filled($this->credentials);
    }

    public function propertyId(): ?string
    {
        return $this->propertyId;
    }

    public function serviceAccountEmail(): ?string
    {
        return $this->credentials['client_email'] ?? null;
    }

    /**
     * Run the smallest possible report. Throws if the connection doesn't work.
     */
    public function ping(): void
    {
        $this->post('runReport', [
            'dateRanges' => [['startDate' => 'yesterday', 'endDate' => 'today']],
            'metrics' => [['name' => 'activeUsers']],
        ]);
    }

    /**
     * People on the site in the last 30 minutes.
     */
    public function activeNow(): int
    {
        $report = $this->post('runRealtimeReport', ['metrics' => [['name' => 'activeUsers']]]);

        return (int) ($report['rows'][0]['metricValues'][0]['value'] ?? 0);
    }

    /**
     * Everything the dashboard shows for the last $days days.
     */
    public function overview(int $days): array
    {
        $current = ['startDate' => ($days - 1).'daysAgo', 'endDate' => 'today', 'name' => 'current'];
        $previous = ['startDate' => (2 * $days - 1).'daysAgo', 'endDate' => $days.'daysAgo', 'name' => 'previous'];

        $top = fn (string $dimension, string $metric, int $limit = 8) => [
            'dateRanges' => [$current],
            'dimensions' => [['name' => $dimension]],
            'metrics' => [['name' => $metric]],
            'orderBys' => [['metric' => ['metricName' => $metric], 'desc' => true]],
            'limit' => $limit,
        ];

        // The API takes at most five reports per batch.
        [$totals, $daily, $pages, $sources, $countries] = $this->batch([
            [
                'dateRanges' => [$current, $previous],
                'metrics' => [['name' => 'activeUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews'], ['name' => 'userEngagementDuration']],
            ],
            [
                'dateRanges' => [$current],
                'dimensions' => [['name' => 'date']],
                'metrics' => [['name' => 'activeUsers'], ['name' => 'screenPageViews']],
                'limit' => 400,
            ],
            $top('pagePath', 'screenPageViews'),
            $top('sessionSource', 'sessions'),
            $top('country', 'activeUsers'),
        ]);

        [$devices, $events] = $this->batch([
            $top('deviceCategory', 'activeUsers', 5),
            $top('eventName', 'eventCount', 20) + [
                'dimensionFilter' => ['filter' => [
                    'fieldName' => 'eventName',
                    'inListFilter' => ['values' => array_keys(self::EVENTS)],
                ]],
            ],
        ]);

        $eventCounts = array_column($events, 'eventCount', 'eventName');

        return [
            'days' => $days,
            'fetched_at' => now()->toIso8601String(),
            'totals' => $this->totals($totals),
            'daily' => $this->daily($daily, $days),
            'pages' => $this->ranked($pages, 'pagePath', 'screenPageViews'),
            'sources' => $this->ranked($sources, 'sessionSource', 'sessions'),
            'countries' => $this->ranked($countries, 'country', 'activeUsers'),
            'devices' => array_map(
                fn ($row) => ['label' => ucfirst($row['label'])] + $row,
                $this->ranked($devices, 'deviceCategory', 'activeUsers'),
            ),
            'events' => collect(self::EVENTS)
                ->map(fn ($label, $name) => ['label' => $label, 'value' => (int) ($eventCounts[$name] ?? 0)])
                ->values()->all(),
        ];
    }

    private function totals(array $rows): array
    {
        $byRange = array_column($rows, null, 'dateRange');
        $perVisitor = fn (array $row) => ($row['activeUsers'] ?? 0) > 0 ? $row['userEngagementDuration'] / $row['activeUsers'] : 0;

        $pair = fn (callable $read) => [
            'value' => $value = $read($byRange['current'] ?? []),
            'previous' => $before = $read($byRange['previous'] ?? []),
            // Percentage change on the previous period; null when there is nothing to compare against.
            'change' => $before > 0 ? ($value - $before) / $before * 100 : null,
        ];

        return [
            'visitors' => $pair(fn ($row) => $row['activeUsers'] ?? 0),
            'sessions' => $pair(fn ($row) => $row['sessions'] ?? 0),
            'views' => $pair(fn ($row) => $row['screenPageViews'] ?? 0),
            'engagement' => $pair($perVisitor),
        ];
    }

    /**
     * One entry per calendar day, including the days Google leaves out because nothing happened.
     */
    private function daily(array $rows, int $days): array
    {
        $byDate = array_column($rows, null, 'date');
        $today = Carbon::today(config('portfolio.profile.timezone'));

        return collect(range($days - 1, 0))->map(function (int $ago) use ($byDate, $today) {
            $day = $today->copy()->subDays($ago);
            $row = $byDate[$day->format('Ymd')] ?? [];

            return [
                'date' => $day->toDateString(),
                'label' => $day->format('j M'),
                'visitors' => (int) ($row['activeUsers'] ?? 0),
                'views' => (int) ($row['screenPageViews'] ?? 0),
            ];
        })->all();
    }

    private function ranked(array $rows, string $dimension, string $metric): array
    {
        $max = max(array_column($rows, $metric) ?: [0]);

        return array_map(fn ($row) => [
            'label' => $row[$dimension],
            'value' => (int) $row[$metric],
            'share' => $max > 0 ? round($row[$metric] / $max * 100, 1) : 0,
        ], $rows);
    }

    /**
     * @return array<int, array> one list of flattened rows per requested report
     */
    private function batch(array $requests): array
    {
        $reports = $this->post('batchRunReports', ['requests' => $requests])['reports'] ?? [];

        return array_map(function (int $i) use ($reports) {
            $report = $reports[$i] ?? [];
            $dimensions = array_column($report['dimensionHeaders'] ?? [], 'name');
            $metrics = array_column($report['metricHeaders'] ?? [], 'name');

            return array_map(fn (array $row) => array_combine($dimensions, array_column($row['dimensionValues'] ?? [], 'value'))
                + array_map('floatval', array_combine($metrics, array_column($row['metricValues'] ?? [], 'value'))),
                $report['rows'] ?? []);
        }, array_keys($requests));
    }

    private function post(string $method, array $body): array
    {
        $url = config('services.google_analytics.api_url')."/properties/{$this->propertyId}:{$method}";

        try {
            $response = Http::withToken($this->accessToken())->timeout(20)->acceptJson()->post($url, $body);
        } catch (ConnectionException) {
            throw new GoogleAnalyticsException("Couldn't reach Google Analytics. Check the server's internet connection and try again.");
        }

        if ($response->failed()) {
            throw GoogleAnalyticsException::fromResponse($response, $this->serviceAccountEmail());
        }

        return $response->json() ?? [];
    }

    /**
     * Exchange a JWT signed with the service account's key for a short-lived access token.
     */
    private function accessToken(): string
    {
        $email = $this->serviceAccountEmail();

        return Cache::remember('ga.token.'.sha1($email.($this->credentials['private_key_id'] ?? '')), 3300, function () use ($email) {
            $tokenUrl = config('services.google_analytics.token_url');
            $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');

            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
                'iss' => $email,
                'scope' => self::SCOPE,
                'aud' => $tokenUrl,
                'iat' => time(),
                'exp' => time() + 3600,
            ]);

            if (! @openssl_sign($unsigned, $signature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new GoogleAnalyticsException("The service-account key couldn't be used to sign a request. Download a fresh JSON key and upload it again.");
            }

            try {
                $response = Http::asForm()->timeout(15)->post($tokenUrl, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '='),
                ]);
            } catch (ConnectionException) {
                throw new GoogleAnalyticsException("Couldn't reach Google to sign in. Check the server's internet connection and try again.");
            }

            if ($response->failed() || blank($response->json('access_token'))) {
                $reason = $response->json('error_description') ?? $response->json('error') ?? 'no reason given';

                throw new GoogleAnalyticsException("Google rejected the service-account key ({$reason}). The key may have been deleted; create a new one and upload it.");
            }

            return $response->json('access_token');
        });
    }
}
