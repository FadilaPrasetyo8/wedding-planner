<?php

namespace Tests\Feature\Stabilization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_representative_public_and_health_pages_remain_available(): void
    {
        $this->get(route('home'))->assertOk();
        $this->getJson(route('ready'))->assertOk()->assertExactJson(['status' => 'ok']);
    }
}
