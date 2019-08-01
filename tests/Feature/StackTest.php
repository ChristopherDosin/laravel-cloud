<?php

namespace Tests\Feature;

use App\Hook;
use App\Stack;
use App\Database;
use App\Balancer;
use App\StackTask;
use App\IpAddress;
use App\AppServer;
use App\WebServer;
use App\Deployment;
use Tests\TestCase;
use App\ServerDeployment;
use App\Jobs\SyncNetwork;
use App\Jobs\MonitorDeployment;
use Illuminate\Support\Facades\Bus;
use App\Jobs\EnsureFloatingIpExists;
use App\Jobs\DeleteServerOnProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackTest extends TestCase
{
    use RefreshDatabase;


    public function test_not_promotable_when_app_server_and_no_serves_directive_is_present()
    {
        $stack = factory(Stack::class)->create();
        $stack->appServer()->save(factory(AppServer::class)->make([
            'meta' => ['serves' => []]
        ]));

        $this->assertFalse($stack->promotable());


        $stack = factory(Stack::class)->create();
        $stack->appServer()->save(factory(AppServer::class)->make([
            'meta' => []
        ]));

        $this->assertFalse($stack->promotable());

        $stack = factory(Stack::class)->create();
        $stack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));
        $this->assertTrue($stack->promotable());
    }


    public function test_can_retrieve_web_addresses()
    {
        $stack = factory(Stack::class)->create();

        $stack->appServers()->save($server1 = factory(AppServer::class)->make());
        $server1->address()->save(factory(IpAddress::class)->make([
            'private_address' => '192.168.1.1',
        ]));

        $stack->webServers()->save($server2 = factory(WebServer::class)->make());
        $server2->address()->save(factory(IpAddress::class)->make([
            'private_address' => '192.168.2.2',
        ]));

        $this->assertEquals(
            ['https://192.168.1.1', 'https://192.168.2.2'],
            $stack->privateWebAddresses()
        );
    }


    public function test_can_determine_the_urls_the_stack_responds_to()
    {
        $stack = factory(Stack::class)->create();

        $stack->appServers()->save($server1 = factory(AppServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $stack->webServers()->save($server2 = factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $this->assertEquals(
            collect([$stack->url.'.laravel.build'])->sort()->values()->all(),
            collect($stack->shouldRespondTo())->sort()->values()->all()
        );

        $this->assertEquals(
            collect([
                $stack->url.'.laravel.build:80',
                $stack->url.'.laravel.build:443',
            ])->sort()->values()->all(),
            collect($stack->shouldRespondToWithPorts())->sort()->values()->all()
        );

        $stack->update(['promoted' => true]);

        $stack = $stack->fresh();

        $this->assertEquals(
            collect([$stack->url.'.laravel.build', 'laravel.com', 'www.laravel.com'])->sort()->values()->all(),
            collect($stack->shouldRespondTo())->sort()->values()->all()
        );

        $this->assertEquals(
            collect([
                'laravel.com:80',
                'laravel.com:443',
                'www.laravel.com:80',
                'www.laravel.com:443',
                $stack->url.'.laravel.build:80',
                $stack->url.'.laravel.build:443',
            ])->sort()->values()->all(),
            collect($stack->shouldRespondToWithPorts())->sort()->values()->all()
        );
    }


    public function test_cannot_provision_if_already_provisioning()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'environment_id' => 1,
            'status' => 'provisioning',
        ]);

        $stack->provision();

        Bus::assertNotDispatched(EnsureFloatingIpExists::class);
    }


    public function test_recommended_balancer_size_returns_proper_size()
    {
        $stack = factory(Stack::class)->create();

        $stack->appServers()->save(factory(AppServer::class)->make(['size' => '2GB']));
        $stack->webServers()->save(factory(WebServer::class)->make(['size' => '2GB']));

        $this->assertEquals('1GB', $stack->recommendedBalancerSize());
    }


    public function test_stack_entrypoint_can_be_determined()
    {
        // With Balancers...
        $stack = factory(Stack::class)->create(['balanced' => true]);
        $stack->environment->project->balancers()->save($balancer = factory(Balancer::class)->make());
        $balancer->address()->save(factory(IpAddress::class)->make());

        $this->assertEquals($balancer->address->public_address, $stack->entrypoint());

        // With Multiple Balancers...
        $stack = factory(Stack::class)->create(['balanced' => true]);
        $stack->environment->project->balancers()->save($balancer = factory(Balancer::class)->make([
            'size' => '2GB',
        ]));
        $stack->environment->project->balancers()->save($balancer2 = factory(Balancer::class)->make([
            'size' => '8GB',
        ]));
        $balancer->address()->save(factory(IpAddress::class)->make());
        $balancer2->address()->save(factory(IpAddress::class)->make());

        $this->assertEquals($balancer2->address->public_address, $stack->entrypoint());
    }


    public function test_stack_entrypoint_returns_master_server_ip_with_only_web_servers()
    {
        $stack = factory(Stack::class)->create();
        $stack->webServers()->save($server = factory(WebServer::class)->make());
        $stack->webServers()->save($server2 = factory(WebServer::class)->make());
        $server->address()->save(factory(IpAddress::class)->make());
        $server2->address()->save(factory(IpAddress::class)->make());

        $this->assertEquals($server->address->public_address, $stack->entrypoint());
    }


    public function test_stack_can_be_deployed()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);
        $stack->deploymentLock()->release();
        $deployment = $stack->deploy('3b478197c05f0bb60ee484e01389bd2fff1d2bfc', ['build'], ['activate']);

        $this->assertTrue($stack->isDeploying());
        $this->assertNotNull($deployment->commit_hash);

        Bus::assertDispatched(MonitorDeployment::class);
    }


    public function test_deleting_stack_deletes_related_entities()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());
        $stack->deployments()->save(factory(Deployment::class)->make());
        $stack->tasks()->save(factory(StackTask::class)->make());
        $stack->hooks()->save(factory(Hook::class)->make());

        $stack->delete();

        $this->assertCount(0, StackTask::all());
        $this->assertCount(0, Deployment::all());
        $this->assertCount(0, Hook::all());

        Bus::assertDispatched(DeleteServerOnProvider::class, function ($job) use ($server) {
            return $job->providerServerId == $server->providerServerId();
        });
    }


    public function test_deleting_stack_detaches_from_all_databases()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->project()->databases()->save($database = factory(Database::class)->make());
        $database->stacks()->sync([$stack->id]);

        $this->assertCount(1, $database->fresh()->stacks);

        $stack->delete();

        $this->assertCount(0, $database->fresh()->stacks);

        Bus::assertDispatched(SyncNetwork::class, function ($job) use ($database) {
            return $job->database->id === $database->id;
        });
    }


    public function test_deleting_stack_deletes_all_deployments()
    {
        $stack = factory(Stack::class)->create();
        $stack->deployments()->save($deployment = factory(Deployment::class)->make());
        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'deployment_id' => $deployment->id,
        ]));

        $this->assertEquals(1, Deployment::count());
        $this->assertEquals(1, ServerDeployment::count());

        $stack->delete();

        $this->assertEquals(0, Deployment::count());
        $this->assertEquals(0, ServerDeployment::count());
    }


    public function test_can_return_canonical_domain()
    {
        // Test non-www...
        $stack = factory(Stack::class)->create();
        $stack->webServers()->save($server = factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $stack = $stack->fresh();

        $this->assertEquals('laravel.com', $stack->canonicalDomain('www.laravel.com'));
        $this->assertEquals('dev.laravel.com', $stack->canonicalDomain('dev.laravel.com'));
        $this->assertEquals('www.laravel.com', $stack->nonCanonicalDomain('laravel.com'));
        $this->assertEquals('dev.laravel.com', $stack->nonCanonicalDomain('dev.laravel.com'));


        // Test www...
        $stack = factory(Stack::class)->create();
        $stack->webServers()->save($server = factory(WebServer::class)->make([
            'meta' => ['serves' => ['www.laravel.com']],
        ]));

        $stack = $stack->fresh();

        $this->assertEquals('www.laravel.com', $stack->canonicalDomain('laravel.com'));
        $this->assertEquals('www.laravel.com', $stack->canonicalDomain('www.laravel.com'));
        $this->assertEquals('dev.laravel.com', $stack->canonicalDomain('dev.laravel.com'));
        $this->assertEquals('foobar.com', $stack->canonicalDomain('foobar.com'));
        $this->assertTrue($stack->isCanonicalDomain('foobar.com'));
        $this->assertTrue($stack->isCanonicalDomain('dev.laravel.com'));

        $this->assertEquals('laravel.com', $stack->nonCanonicalDomain('www.laravel.com'));
        $this->assertEquals('laravel.com', $stack->nonCanonicalDomain('laravel.com'));
    }


    public function test_can_deploy_pending_deployments()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->update([
            'name' => 'stack-1',
        ]);

        $hook->stack->storePendingDeployment($hook, 'd8f05f1696032982dd8bf77aa9186d2aea744801');

        $deployment = $hook->stack->deployPending();

        $this->assertInstanceOf(Deployment::class, $deployment);
        $this->assertNull($deployment->branch);
        $this->assertEquals('d8f05f1696032982dd8bf77aa9186d2aea744801', $deployment->commit_hash);
    }


    public function test_pending_deployments_not_deployed_for_commits_that_dont_exist()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $hook->stack->deploymentLock()->release();

        $hook->stack->environment->update([
            'name' => 'workbench',
        ]);

        $hook->stack->storePendingDeployment($hook, 'asldjfalkjdkjdjsjasdlkj');

        $this->assertNull($hook->stack->deployPending());
    }
}
