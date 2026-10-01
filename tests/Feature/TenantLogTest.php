<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TenantLogTest extends TestCase
{
    use RefreshDatabase;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep test logs out of the real storage/logs directory
        $this->storagePath = sys_get_temp_dir() . '/pos-tenant-log-test-' . uniqid();
        File::ensureDirectoryExists($this->storagePath . '/logs');
        $this->app->useStoragePath($this->storagePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storagePath);

        parent::tearDown();
    }

    public function test_logs_are_written_to_the_current_tenants_file(): void
    {
        $restaurant = Restaurant::create(['name' => 'Alpha', 'subdomain' => 'alpha']);
        $user = User::factory()->create(['restaurant_id' => $restaurant->id]);
        $this->actingAs($user, 'api');

        Log::channel('tenant')->info('tenant log check');

        $path = "{$this->storagePath}/logs/tenant-{$restaurant->id}.log";
        $this->assertFileExists($path);
        $this->assertStringContainsString('tenant log check', file_get_contents($path));
    }

    public function test_logs_without_tenant_go_to_the_global_file(): void
    {
        Log::channel('tenant')->info('global log check');

        $path = "{$this->storagePath}/logs/tenant-global.log";
        $this->assertFileExists($path);
        $this->assertStringContainsString('global log check', file_get_contents($path));
    }
}
