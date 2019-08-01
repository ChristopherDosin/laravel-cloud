<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use App\AppServer;
use Tests\TestCase;
use App\Environment;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvironmentControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_environments_can_be_listed()
    {
        $environment = factory(Environment::class)->create();

        $response = $this->actingAs($environment->project->user, 'api')
                    ->get('/api/project/'.$environment->project->id.'/environments');

        $response->assertStatus(200);
        $this->assertCount(1, $response->original);
        $this->assertEquals($environment->id, $response->original[0]->id);
    }


    public function test_environment_variables_can_be_retrieved()
    {
        $environment = factory(Environment::class)->create([
            'variables' => 'APP_DEBUG=true'
        ]);

        $response = $this->actingAs($environment->project->user, 'api')
                    ->get('/api/environment/'.$environment->id);

        $response->assertStatus(200);
        $this->assertEquals('APP_DEBUG=true', $response->original->variables);
    }


    public function test_environments_can_be_created()
    {
        $project = factory(Project::class)->create();

        $response = $this->actingAs($project->user, 'api')
                    ->post('/api/project/'.$project->id.'/environment', [
                        'name' => 'Test Environment',
                        'variables' => 'APP_DEBUG=true'
                    ]);

        $response->assertStatus(201);
        $this->assertInstanceOf(Environment::class, $response->original);
    }


    public function test_duplicate_environment_names_can_not_be_created()
    {
        $environment = factory(Environment::class)->create([
            'name' => 'Test Environment',
        ]);

        $response = $this->withExceptionHandling()->actingAs($environment->project->user, 'api')
                    ->json('POST', '/api/project/'.$environment->project->id.'/environment', [
                        'name' => 'Test Environment',
                        'variables' => 'APP_DEBUG=true'
                    ]);

        $response->assertStatus(422);
    }


    public function test_environments_can_be_updated()
    {
        $environment = factory(Environment::class)->create([
            'name' => 'Test Environment',
            'variables' => 'APP_DEBUG=true',
        ]);

        $response = $this->actingAs($environment->project->user, 'api')
                    ->json('PUT', '/api/environment/'.$environment->id, [
                        'variables' => 'APP_DEBUG=false',
                    ]);

        $response->assertStatus(200);
        $this->assertEquals('APP_DEBUG=false', $environment->fresh()->variables);
    }


    public function test_non_collaborator_cant_update_environment()
    {
        $environment = factory(Environment::class)->create([
            'name' => 'Test Environment',
            'variables' => 'APP_DEBUG=true',
        ]);

        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('PUT', '/api/environment/'.$environment->id, [
                        'variables' => 'APP_DEBUG=false',
                    ]);

        $response->assertStatus(403);
    }


    public function test_collaborator_can_update_environment()
    {
        $environment = factory(Environment::class)->create([
            'name' => 'Test Environment',
            'variables' => 'APP_DEBUG=true',
        ]);

        $user = $this->user();
        $environment->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('PUT', '/api/environment/'.$environment->id, [
                        'variables' => 'APP_DEBUG=false',
                    ]);

        $response->assertStatus(200);
    }


    public function test_environments_can_be_deleted()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsProvisioned();
        $environment = $server->stack->environment;

        $response = $this->actingAs($environment->project->user, 'api')->json(
            'DELETE', '/api/environment/'.$environment->id
        );

        $response->assertStatus(200);

        $this->assertCount(0, Environment::all());
        $this->assertCount(0, Stack::all());
        $this->assertCount(0, AppServer::all());
    }


    public function test_environments_cant_be_deleted_without_permission()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsProvisioned();
        $environment = $server->stack->environment;

        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'DELETE', '/api/environment/'.$environment->id
        );

        $response->assertStatus(403);
    }


    public function test_environments_can_always_be_deleted_by_their_creator()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsProvisioned();
        $environment = $server->stack->environment;

        $user = $this->user();
        $environment->update(['creator_id' => $user->id]);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'DELETE', '/api/environment/'.$environment->id
        );

        $response->assertStatus(200);
    }


    public function test_environment_cant_be_deleted_if_their_stacks_are_provisioning()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->update(['status' => 'provisioning']);
        $environment = $server->stack->environment;

        $response = $this->withExceptionHandling()->actingAs($environment->project->user, 'api')->json(
            'DELETE', '/api/environment/'.$environment->id
        );

        $response->assertStatus(422);
    }


    public function test_environment_cant_be_deleted_if_their_stacks_are_deploying()
    {
        Bus::fake();

        $server = factory(AppServer::class)->create();
        $server->stack->markAsDeploying();
        $environment = $server->stack->environment;

        $response = $this->withExceptionHandling()->actingAs($environment->project->user, 'api')->json(
            'DELETE', '/api/environment/'.$environment->id
        );

        $response->assertStatus(422);
    }
}
