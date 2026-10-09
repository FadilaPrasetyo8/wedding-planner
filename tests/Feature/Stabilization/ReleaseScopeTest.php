<?php

namespace Tests\Feature\Stabilization;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReleaseScopeTest extends TestCase
{
    public function test_forbidden_v1_surfaces_are_not_registered(): void
    {
        $routeNames = collect(Route::getRoutes())->map(fn ($route): string => $route->getName() ?? '')->implode('\n');

        $this->assertStringNotContainsString('subscription', strtolower($routeNames));
        $this->assertStringNotContainsString('billing', strtolower($routeNames));
        $this->assertStringNotContainsString('vendor.auth', strtolower($routeNames));
        $this->assertStringNotContainsString('marketplace', strtolower($routeNames));
    }

    public function test_readiness_and_export_routes_exist_without_public_storage_urls(): void
    {
        $this->assertTrue(Route::getRoutes()->getByName('ready') !== null);
        $this->assertTrue(Route::getRoutes()->getByName('weddings.exports.budget') !== null);
        $filesystems = file_get_contents(base_path('config/filesystems.php'));
        $this->assertIsString($filesystems);
        $this->assertStringContainsString("'attachment_disk'", $filesystems);
    }
}
