<?php

namespace Tests\Feature;

use App\Balancer;
use Tests\TestCase;
use App\Scripts\ProvisionBalancer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionBalancerScriptTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_script_can_be_rendered()
    {
        $balancer = factory(Balancer::class)->create();

        $script = new ProvisionBalancer($balancer);

        $script = $script->script();

        $this->assertNotNull($script);
    }
}
