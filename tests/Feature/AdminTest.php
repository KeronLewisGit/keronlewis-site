<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API = 'https://analyticsdata.googleapis.com/v1beta/properties/412345678';

    /** A throwaway service-account key, so the real JWT signing code runs. */
    private function key(): array
    {
        static $privateKey;
        $privateKey ??= (function () {
            openssl_pkey_export(openssl_pkey_new(['private_key_bits' => 2048]), $pem);

            return $pem;
        })();

        return [
            'type' => 'service_account',
            'client_email' => 'reader@example-project.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'private_key_id' => 'abc123',
        ];
    }

    private function connect(): void
    {
        Setting::write('ga_property_id', '412345678');
        Setting::write('ga_credentials', json_encode($this->key()));
    }

    private function report(array $dimensions, array $metrics, array $rows): array
    {
        return [
            'dimensionHeaders' => array_map(fn ($name) => ['name' => $name], $dimensions),
            'metricHeaders' => array_map(fn ($name) => ['name' => $name], $metrics),
            'rows' => array_map(fn ($row) => [
                'dimensionValues' => array_map(fn ($value) => ['value' => (string) $value], array_slice($row, 0, count($dimensions))),
                'metricValues' => array_map(fn ($value) => ['value' => (string) $value], array_slice($row, count($dimensions))),
            ], $rows),
        ];
    }

    private function fakeGoogle(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'test-token', 'expires_in' => 3600]),
            self::API.':runReport' => Http::response(['rowCount' => 0]),
            self::API.':runRealtimeReport' => Http::response($this->report([], ['activeUsers'], [[3]])),
            self::API.':batchRunReports' => fn (Request $request) => Http::response(['reports' => count($request['requests']) === 5
                ? [
                    $this->report(['dateRange'], ['activeUsers', 'sessions', 'screenPageViews', 'userEngagementDuration'], [
                        ['current', 120, 150, 420, 9000],
                        ['previous', 100, 140, 300, 6000],
                    ]),
                    $this->report(['date'], ['activeUsers', 'screenPageViews'], [[now(config('portfolio.profile.timezone'))->format('Ymd'), 17, 44]]),
                    $this->report(['pagePath'], ['screenPageViews'], [['/', 260], ['/resume', 160]]),
                    $this->report(['sessionSource'], ['sessions'], [['linkedin.com', 80], ['(direct)', 50]]),
                    $this->report(['country'], ['activeUsers'], [['Trinidad & Tobago', 70]]),
                ]
                : [
                    $this->report(['deviceCategory'], ['activeUsers'], [['desktop', 90], ['mobile', 30]]),
                    $this->report(['eventName'], ['eventCount'], [['resume_download', 12]]),
                ]]),
        ]);
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/connection')->assertRedirect(route('admin.login'));
    }

    public function test_the_admin_can_sign_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'a-long-password']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'a-long-password'])->assertRedirect(route('admin.analytics'));
        $this->assertAuthenticatedAs($user);

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_the_admin_command_creates_a_login(): void
    {
        $this->artisan('portfolio:admin', ['--email' => 'me@example.com', '--password' => 'a-long-password'])->assertSuccessful();
        $this->artisan('portfolio:admin', ['--email' => 'me@example.com', '--password' => 'short'])->assertFailed();

        $this->post('/admin/login', ['email' => 'me@example.com', 'password' => 'a-long-password'])->assertRedirect(route('admin.analytics'));
    }

    public function test_dashboard_prompts_to_connect_when_not_connected(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertOk()
            ->assertSee("Google Analytics isn't connected yet", false);
    }

    public function test_dashboard_shows_reports_from_google_analytics(): void
    {
        $this->connect();
        $this->fakeGoogle();

        $this->actingAs(User::factory()->create())->get('/admin?days=7')
            ->assertOk()
            ->assertSeeInOrder(['Visitors', '120', '+20%', 'vs previous 7 days'])
            ->assertSeeInOrder(['Time per visitor', '1m 15s'])
            ->assertSeeInOrder(['Active now', '3'])
            ->assertSeeInOrder(['Top pages', '/resume', '160'])
            ->assertSee('linkedin.com')
            ->assertSeeInOrder(['Résumé PDF downloads', '12']);

        // The token request carries a signed JWT, and report calls carry the token.
        Http::assertSent(fn (Request $request) => $request->url() === self::TOKEN_URL && substr_count($request['assertion'], '.') === 2);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), ':batchRunReports') && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_reports_are_cached_until_refreshed(): void
    {
        $this->connect();
        $this->fakeGoogle();
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin')->assertOk();
        Http::assertSentCount(4); // token, two report batches, realtime

        $this->actingAs($admin)->post('/admin/analytics/refresh')->assertRedirect(route('admin.analytics'));
        $this->actingAs($admin)->get('/admin')->assertOk();
        Http::assertSentCount(7);
    }

    public function test_a_google_error_is_explained_instead_of_crashing(): void
    {
        $this->connect();
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'test-token']),
            '*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED', 'message' => 'User does not have sufficient permissions for this property.']], 403),
        ]);

        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertOk()
            ->assertSee("Couldn't load the reports", false)
            ->assertSee('reader@example-project.iam.gserviceaccount.com');
    }

    public function test_saving_a_working_connection_stores_it_encrypted(): void
    {
        $this->fakeGoogle();
        $key = $this->key();

        $this->actingAs(User::factory()->create())->put('/admin/connection', [
            'measurement_id' => 'g-ab12cd34ef',
            'property_id' => '412345678',
            'credentials' => UploadedFile::fake()->createWithContent('key.json', json_encode($key)),
        ])->assertRedirect(route('admin.analytics'));

        $this->assertSame('G-AB12CD34EF', Setting::read('ga_measurement_id'));
        $this->assertSame($key['client_email'], json_decode(Setting::read('ga_credentials'), true)['client_email']);
        $this->assertStringNotContainsString('PRIVATE KEY', Setting::query()->toBase()->where('key', 'ga_credentials')->value('value'));
    }

    public function test_a_connection_that_fails_the_test_is_not_saved(): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'test-token']),
            '*' => Http::response(['error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Nope.']], 403),
        ]);

        $this->actingAs(User::factory()->create())->put('/admin/connection', [
            'property_id' => '412345678',
            'credentials' => UploadedFile::fake()->createWithContent('key.json', json_encode($this->key())),
        ])->assertSessionHasErrors('property_id');

        $this->assertNull(Setting::read('ga_property_id'));
        $this->assertNull(Setting::read('ga_credentials'));
    }

    public function test_a_file_that_is_not_a_service_account_key_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/connection', [
            'property_id' => '412345678',
            'credentials' => UploadedFile::fake()->createWithContent('key.json', '{"hello":"world"}'),
        ])->assertSessionHasErrors('credentials');
    }

    public function test_disconnecting_deletes_the_key(): void
    {
        $this->connect();

        $this->actingAs(User::factory()->create())->delete('/admin/connection')->assertRedirect(route('admin.connection'));

        $this->assertNull(Setting::read('ga_credentials'));
    }

    public function test_analytics_waits_for_consent_and_is_skipped_for_the_admin(): void
    {
        $this->get('/')->assertDontSee('ga-id')->assertDontSee('data-consent', false);

        Setting::write('ga_measurement_id', 'G-AB12CD34EF');
        Cache::flush();

        // Visitors get the ID and the consent banner, but no Google script in the page itself.
        $this->get('/')
            ->assertSee('<meta name="ga-id" content="G-AB12CD34EF">', false)
            ->assertSee('data-consent-allow', false)
            ->assertSee('Cookie settings')
            ->assertDontSee('googletagmanager.com');

        $this->actingAs(User::factory()->create())->get('/')->assertDontSee('ga-id')->assertDontSee('data-consent', false);
    }

    public function test_search_console_code_is_saved_from_a_pasted_tag_and_shown_on_public_pages(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/connection', [
            'search_console' => '<meta name="google-site-verification" content="abcDEF123_-abcDEF123_-abcDEF123" />',
        ])->assertRedirect(route('admin.connection'));

        auth()->logout();
        $this->get('/')->assertSee('<meta name="google-site-verification" content="abcDEF123_-abcDEF123_-abcDEF123">', false);
    }

    public function test_privacy_page_describes_analytics_only_when_it_is_on(): void
    {
        $this->get('/privacy')->assertOk()->assertSee("doesn't use any analytics", false);

        Setting::write('ga_measurement_id', 'G-AB12CD34EF');
        Cache::flush();

        $this->get('/privacy')->assertOk()->assertSee('Google Analytics')->assertSee('open cookie settings');
    }
}
