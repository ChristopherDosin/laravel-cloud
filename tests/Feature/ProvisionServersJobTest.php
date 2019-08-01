<?php

namespace Tests\Feature;

use App\Stack;
use App\AppServer;
use Carbon\Carbon;
use Tests\TestCase;
use App\Jobs\ProvisionServers;
use App\Jobs\ProvisionAppServer;
use Illuminate\Support\Facades\Bus;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProvisionServersJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_servers_are_created_and_provisioning_jobs_are_dispatched()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make(['provider_server_id' => null]));

        ServerProviderClientFactory::shouldReceive('make->createServer')->andReturn('123');

        $job = new ProvisionServers($stack);
        $job->handle();

        Bus::assertDispatched(ProvisionAppServer::class);
        $this->assertEquals(123, $server->fresh()->providerServerId());
        $this->assertEquals(1, $stack->fresh()->initial_server_count);
    }


    public function test_servers_are_not_created_if_provider_id_already_present()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());

        ServerProviderClientFactory::shouldReceive('make->createServer')->never();

        $job = new ProvisionServers($stack);
        $job->handle();

        Bus::assertDispatched(ProvisionAppServer::class);
    }


    public function test_provisioning_job_not_dispatched_if_already_dispatched()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make([
            'provisioning_job_dispatched_at' => Carbon::now(),
        ]));

        ServerProviderClientFactory::shouldReceive('make->createServer')->never();

        $job = new ProvisionServers($stack);
        $job->handle();

        Bus::assertNotDispatched(ProvisionAppServer::class);
    }
}
