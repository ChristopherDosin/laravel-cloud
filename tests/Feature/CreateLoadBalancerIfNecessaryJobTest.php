<?php

namespace Tests\Feature;

use App\Stack;
use App\AppServer;
use App\WebServer;
use Tests\TestCase;
use App\Jobs\CreateLoadBalancerIfNecessary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateLoadBalancerIfNecessaryJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_balancer_is_provisioned_if_multiple_servers_are_present_and_balancer_doesnt_exist()
    {
        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->create(['size' => '2GB']));
        $stack->webServers()->save(factory(WebServer::class)->create(['size' => '2GB']));
        $stack->setRelation('environment', (object) ['project' => $fake = new CreateLoadBalancerIfNecessaryJobTestFakeProject]);

        $job = new CreateLoadBalancerIfNecessary($stack);
        $job->handle();

        $this->assertEquals('balancer', $fake->name);
        $this->assertEquals('1GB', $fake->size);
        $this->assertTrue($stack->balanced);
    }


    public function test_balancer_is_not_provisioned_if_a_balancer_already_exists()
    {
        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->create(['size' => '2GB']));
        $fake = new CreateLoadBalancerIfNecessaryJobTestFakeProject;
        $fake->balancers = collect([(object) []]);
        $stack->setRelation('environment', (object) ['project' => $fake]);

        $job = new CreateLoadBalancerIfNecessary($stack);
        $job->handle();

        $this->assertNull($fake->name);
        $this->assertNull($fake->size);
        $this->assertTrue($stack->balanced);
    }


    public function test_balancer_is_not_provisioned_if_only_single_web_server()
    {
        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->create(['size' => '2GB']));
        $stack->setRelation('environment', (object) ['project' => $fake = new CreateLoadBalancerIfNecessaryJobTestFakeProject]);

        $job = new CreateLoadBalancerIfNecessary($stack);
        $job->handle();

        $this->assertNull($fake->name);
        $this->assertNull($fake->size);
        $this->assertFalse($stack->balanced);
    }


    public function test_balancer_is_not_provisioned_if_only_single_app_server()
    {
        $stack = factory(Stack::class)->create();

        $stack->appServers()->save(factory(AppServer::class)->create(['size' => '2GB']));
        $stack->setRelation('environment', (object) ['project' => $fake = new CreateLoadBalancerIfNecessaryJobTestFakeProject]);

        $job = new CreateLoadBalancerIfNecessary($stack);
        $job->handle();

        $this->assertNull($fake->name);
        $this->assertNull($fake->size);
        $this->assertFalse($stack->balanced);
    }
}


class CreateLoadBalancerIfNecessaryJobTestFakeProject
{
    public $balancers;
    public $name;
    public $size;

    public function __construct()
    {
        $this->balancers = collect();
    }

    public function provisionBalancer($name, $size)
    {
        $this->name = $name;
        $this->size = $size;
    }
}
