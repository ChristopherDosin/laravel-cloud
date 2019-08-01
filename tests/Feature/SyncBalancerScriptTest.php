<?php

namespace Tests\Feature;

use App\Balancer;
use Tests\TestCase;
use App\Scripts\SyncBalancer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncBalancerScriptTest extends TestCase
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

        $script = new SyncBalancer($balancer);

        $this->assertNotNull($script->script());
    }
}
