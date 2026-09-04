<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class OptionalAuthenticationFeaturesTest extends TestCase
{
    public function test_passkey_and_two_factor_routes_are_not_registered(): void
    {
        $routeNames = collect(app('router')->getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter();

        $this->assertFalse($routeNames->contains(fn (string $name) => str_starts_with($name, 'passkey.')));
        $this->assertFalse($routeNames->contains(fn (string $name) => str_starts_with($name, 'two-factor.')));
        $this->assertFalse($routeNames->contains('well-known.passkeys'));
    }
}
