<?php

namespace Tests\Unit;

use Tests\TestCase;

class PostgreSqlEnvironmentTest extends TestCase
{
    public function test_automated_tests_use_the_dedicated_postgresql_database(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('pgsql', config('database.default'));
        $this->assertSame('wedding_planner_test', config('database.connections.pgsql.database'));
        $this->assertNotSame(
            'wedding_planner',
            config('database.connections.pgsql.database'),
        );
    }
}
