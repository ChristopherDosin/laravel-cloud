<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use App\Balancer;
use App\IpAddress;
use App\WebServer;
use Tests\TestCase;
use App\Environment;
use App\Jobs\ProvisionBalancer;
use App\Jobs\UpdateStackDnsRecords;
use Illuminate\Support\Facades\Bus;
use App\Jobs\DeleteServerOnProvider;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BalancerControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_balancers_can_be_listed()
    {
        $balancer = factory(Balancer::class)->create();

        $response = $this->actingAs($balancer->project->user, 'api')
                    ->get('/api/project/'.$balancer->project->id.'/balancers');

        $response->assertStatus(200);
        $this->assertCount(1, $response->original);
        $this->assertEquals($balancer->id, $response->original[0]->id);
    }


    public function test_duplicate_balancer_names_cant_be_created()
    {
        $balancer = factory(Balancer::class)->create(['name' => 'main']);

        $response = $this->withExceptionHandling()
                ->actingAs($balancer->project->user, 'api')
                ->json('POST', '/api/project/'.$balancer->project->id.'/balancer', [
                    'name' => 'main',
                    'size' => '2GB',
                ]);

        $response->assertStatus(422);
    }


    public function test_balancers_can_be_created()
    {
        Bus::fake();

        $project = factory(Project::class)->create();

        ServerProviderClientFactory::shouldReceive('make->sizes')->andReturn([
            '2GB' => [],
        ]);

        ServerProviderClientFactory::shouldReceive('make->createServer')
                        ->with('main', '2GB', 'nyc3')
                        ->andReturn('123');

        $response = $this->actingAs($project->user, 'api')->json('POST', '/api/project/'.$project->id.'/balancer', [
            'name' => 'main',
            'size' => '2GB',
        ]);

        Bus::assertDispatched(ProvisionBalancer::class);

        $this->assertCount(1, $project->balancers);
        $this->assertEquals('main', $project->balancers[0]->name);
        $this->assertEquals('2GB', $project->balancers[0]->size);
    }


    public function test_balancers_can_be_deleted()
    {
        Bus::fake();

        $balancer = factory(Balancer::class)->create();
        $balancer->address()->save($address = factory(IpAddress::class)->make());

        $balancer->project->balancers()->save(factory(Balancer::class)->make());

        $response = $this->actingAs($balancer->project->user, 'api')
                    ->delete('/api/balancer/'.$balancer->id);

        $response->assertStatus(200);
        $this->assertNull(Balancer::find($balancer->id));
        $this->assertNull(IpAddress::where('public_address', $address->public_address)->first());

        Bus::assertDispatched(UpdateStackDnsRecords::class);

        Bus::assertDispatched(DeleteServerOnProvider::class, function ($job) use ($balancer) {
            return $job->project->id == $balancer->project->id &&
                   $job->providerServerId == $balancer->providerServerId();
        });
    }


    public function test_balancers_cant_be_deleted_when_they_are_last_balancer_and_have_balanced_stacks()
    {
        Bus::fake();

        $balancer = factory(Balancer::class)->create();
        $balancer->address()->save($address = factory(IpAddress::class)->make());

        $project = $balancer->project;
        $project->environments()->save($environment = factory(Environment::class)->make());

        $environment->stacks()->save($stack = factory(Stack::class)->make(['balanced' => true]));
        $stack->webServers()->save(factory(WebServer::class)->make());
        $stack->webServers()->save(factory(WebServer::class)->make());

        $response = $this->withExceptionHandling()->actingAs($balancer->project->user, 'api')
                    ->json('DELETE', '/api/balancer/'.$balancer->id);

        $response->assertStatus(422);

        Bus::assertNotDispatched(DeleteServerOnProvider::class);
    }
}
