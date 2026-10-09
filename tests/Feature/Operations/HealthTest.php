<?php

namespace Tests\Feature\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_returns_ok_when_dependencies_are_available(): void
    {
        $requestId = '6f6a2d77-5d75-4f16-8b3b-8e9dcb4cf7c0';

        $response = $this->withHeader('X-Request-ID', $requestId)->getJson(route('ready'));

        $response->assertOk()->assertExactJson(['status' => 'ok']);
        $response->assertHeader('X-Request-ID', $requestId);
        $this->assertSame($requestId, $response->headers->get('X-Request-ID'));
    }

    public function test_malformed_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-ID', 'not-a-uuid')->getJson(route('ready'));

        $response->assertOk()->assertHeader('X-Request-ID');
        $this->assertNotSame('not-a-uuid', $response->headers->get('X-Request-ID'));
        $this->assertTrue((bool) preg_match('/^[0-9a-f-]{36}$/', (string) $response->headers->get('X-Request-ID')));
    }

    public function test_readiness_returns_generic_unavailable_response_when_database_fails(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('secret DSN details'));

        $response = $this->getJson(route('ready'));

        $response->assertServiceUnavailable()->assertExactJson(['status' => 'unavailable']);
        $this->assertStringNotContainsString('secret DSN details', $response->getContent());
    }
}
