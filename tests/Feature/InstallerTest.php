<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Use a test lock file so we don't interfere
        Config::set('installer.lock_file', storage_path('app/private/test_installed'));
        @unlink(config('installer.lock_file'));
    }

    protected function tearDown(): void
    {
        @unlink(config('installer.lock_file'));
        parent::tearDown();
    }

    public function test_uninstalled_redirects_to_install()
    {
        $response = $this->get('/');
        $response->assertRedirect('/install');
    }

    public function test_requirements_page_shows()
    {
        $response = $this->get('/install/requirements');
        $response->assertStatus(200);
        $response->assertSee('System Requirements');
    }
}
