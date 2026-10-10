<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class OperationalArtifactsTest extends TestCase
{
    public function test_production_environment_template_is_server_only(): void
    {
        $contents = file_get_contents(base_path('deploy/env.production.example'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $contents);
        $this->assertStringContainsString('SUPABASE_STORAGE_SECRET=', $contents);
        $this->assertStringNotContainsString('VITE_SUPABASE', $contents);
    }

    public function test_scripts_have_safe_contracts(): void
    {
        foreach (['backup-database.sh', 'restore-database.sh', 'release.sh', 'smoke-test.sh'] as $script) {
            $contents = file_get_contents(base_path("scripts/$script"));
            $this->assertIsString($contents);
            $this->assertStringContainsString('set -Eeuo pipefail', $contents);
            $this->assertStringNotContainsString('echo "$PGPASSWORD"', $contents);
        }

        $restore = file_get_contents(base_path('scripts/restore-database.sh'));
        $smoke = file_get_contents(base_path('scripts/smoke-test.sh'));
        $this->assertIsString($restore);
        $this->assertIsString($smoke);
        $this->assertStringContainsString('--confirm-database=', $restore);
        $this->assertStringContainsString('check_status /up 200', $smoke);
        $this->assertStringContainsString('check_status /ready 200', $smoke);
        $this->assertStringContainsString('check_status /dashboard 302', $smoke);
    }
}
