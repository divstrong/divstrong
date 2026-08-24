<?php

namespace Tests\Feature;

use App\Models\BugReporterSite;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BugReporterConfigTest extends TestCase
{
    use RefreshDatabase;

    private function makeSite(bool $isActive): BugReporterSite
    {
        $client = Client::create([
            'name' => 'Acme',
            'email' => 'acme@example.com',
        ]);

        return BugReporterSite::create([
            'client_id' => $client->id,
            'name' => 'Acme Site',
            'domain' => 'acme.test',
            'is_active' => $isActive,
        ]);
    }

    public function test_active_site_reports_active(): void
    {
        $site = $this->makeSite(true);

        $this->getJson('/api/bug-reports/config?key='.$site->public_key)
            ->assertOk()
            ->assertExactJson(['active' => true]);
    }

    public function test_inactive_site_reports_inactive_so_the_widget_does_not_render(): void
    {
        $site = $this->makeSite(false);

        $this->getJson('/api/bug-reports/config?key='.$site->public_key)
            ->assertOk()
            ->assertExactJson(['active' => false]);
    }

    public function test_unknown_or_missing_key_reports_inactive(): void
    {
        $this->getJson('/api/bug-reports/config?key=bk_does_not_exist')
            ->assertOk()
            ->assertExactJson(['active' => false]);

        $this->getJson('/api/bug-reports/config')
            ->assertOk()
            ->assertExactJson(['active' => false]);
    }

    public function test_config_response_is_readable_from_a_cross_origin_embed(): void
    {
        $site = $this->makeSite(true);

        $response = $this->call(
            'GET',
            '/api/bug-reports/config',
            ['key' => $site->public_key],
            [],
            [],
            ['HTTP_ORIGIN' => 'https://acme.test']
        );

        $response->assertOk();

        // Laravel's global HandleCors middleware has the final say on this
        // header and answers api/* with '*'; the controller's own value is a
        // fallback for when that middleware is not in play. Either satisfies
        // the widget, which fetches with credentials omitted.
        $this->assertContains(
            $response->headers->get('Access-Control-Allow-Origin'),
            ['*', 'https://acme.test']
        );
        $this->assertStringContainsString('GET', $response->headers->get('Access-Control-Allow-Methods'));
        // Symfony re-serialises the directives, so assert on them individually.
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('max-age=60', $cacheControl);
    }

    public function test_inactive_site_still_cannot_submit_a_report(): void
    {
        $site = $this->makeSite(false);

        $this->postJson('/api/bug-reports', [
            'what_happened' => 'Something broke',
            'url' => 'https://acme.test/page',
        ], ['X-Site-Key' => $site->public_key])
            ->assertStatus(401);
    }
}
