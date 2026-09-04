<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    public function test_authentication_mutations_use_web_csrf_middleware(): void
    {
        $routeNames = [
            'register.store',
            'login.store',
            'logout',
            'password.email',
            'password.update',
            'verification.send',
            'profile.update',
            'profile.destroy',
            'user-password.update',
        ];

        foreach ($routeNames as $routeName) {
            $middleware = app('router')->getRoutes()->getByName($routeName)?->gatherMiddleware();

            $this->assertNotNull($middleware, "Route [{$routeName}] is not registered.");
            $this->assertContains('web', $middleware, "Route [{$routeName}] is missing web middleware.");
            $this->assertNotContains(
                ValidateCsrfToken::class.':except',
                $middleware,
                "Route [{$routeName}] bypasses CSRF validation.",
            );
        }
    }
}
