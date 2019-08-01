<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use App\Balancer;
use App\Database;
use App\AppServer;
use Tests\TestCase;
use App\SourceProvider;
use App\ServerProvider;
use App\Jobs\ProvisionDatabase;
use Illuminate\Support\Facades\Bus;
use Facades\App\ServerProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_projects_can_be_listed()
    {
        $project = factory(Project::class)->create();
        $project->user->projects()->save(factory(Project::class)->make());
        $project->user->projects()->save(factory(Project::class)->make([
            'archived' => true,
        ]));

        $response = $this->actingAs($project->user, 'api')->json('GET', '/api/projects');

        $response->assertStatus(200);
        $this->assertCount(2, $response->original);
    }


    public function test_no_database_is_created_if_no_database_is_specified()
    {
        Bus::fake();

        $provider = factory(ServerProvider::class)->create();
        $provider->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());

        ServerProviderClientFactory::shouldReceive('make->createServer')->never();

        ServerProviderClientFactory::shouldReceive('make->regions')->andReturn([
            'nyc3' => 'New York 3',
        ]);

        $response = $this->actingAs($provider->user, 'api')->json('POST', '/api/project', [
            'name' => 'Laravel',
            'server_provider_id' => $provider->id,
            'region' => 'nyc3',
            'source_provider_id' => $source->id,
            'repository' => 'taylorotwell/hello-world',
        ]);

        $response->assertStatus(201);

        Bus::assertNotDispatched(ProvisionDatabase::class);

        $project = $provider->user->projects()->first();
        $this->assertCount(0, $project->databases);
        $this->assertEquals('nyc3', $project->region);
        $this->assertEquals('Laravel', $project->name);
    }


    public function test_job_to_provision_database_server_is_dispatched()
    {
        Bus::fake();

        $provider = factory(ServerProvider::class)->create();
        $provider->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());

        ServerProviderClientFactory::shouldReceive('make->createServer')->andReturn(123);

        ServerProviderClientFactory::shouldReceive('make->regions')->andReturn([
            'nyc3' => 'New York 3',
        ]);

        ServerProviderClientFactory::shouldReceive('make->sizes')->andReturn([
            '2GB' => '',
        ]);

        $response = $this->actingAs($provider->user, 'api')->json('POST', '/api/project', [
            'name' => 'Laravel',
            'server_provider_id' => $provider->id,
            'region' => 'nyc3',
            'source_provider_id' => $source->id,
            'repository' => 'taylorotwell/hello-world',
            'database' => 'mysql',
            'database_size' => '2GB',
        ]);

        $response->assertStatus(201);

        Bus::assertDispatched(ProvisionDatabase::class);

        $project = $provider->user->projects()->first();
        $this->assertCount(1, $project->databases);
        $this->assertEquals('nyc3', $project->region);
        $this->assertEquals('Laravel', $project->name);
        $this->assertEquals('mysql', $project->databases->first()->name);
        $this->assertEquals('2GB', $project->databases->first()->size);
        $this->assertEquals(123, $project->databases->first()->provider_server_id);
    }


    public function test_projects_can_be_deleted()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsProvisioned();
        $project = $server->stack->project();
        $project->balancers()->save(factory(Balancer::class)->make());
        $project->databases()->save(factory(Database::class)->make());

        $response = $this->actingAs($project->user, 'api')->json('DELETE', '/api/project/'.$project->id);

        $response->assertStatus(200);

        $this->assertCount(0, AppServer::all());
        $this->assertCount(0, Stack::all());
        $this->assertCount(0, Balancer::all());
        $this->assertCount(0, Database::all());

        $this->assertTrue($project->fresh()->archived);
    }


    public function test_project_cant_be_deleted_if_stacks_are_provisioning()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->update(['status' => 'provisioning']);
        $project = $server->stack->project();

        $response = $this->withExceptionHandling()->actingAs($project->user, 'api')
                    ->json('DELETE', '/api/project/'.$project->id);

        $response->assertStatus(422);
    }


    public function test_project_cant_be_deleted_if_stacks_are_deploying()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsDeploying();
        $project = $server->stack->project();

        $response = $this->withExceptionHandling()->actingAs($project->user, 'api')
                    ->json('DELETE', '/api/project/'.$project->id);

        $response->assertStatus(422);
    }
}
