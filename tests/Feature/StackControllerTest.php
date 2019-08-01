<?php

namespace Tests\Feature;

use App\User;
use App\Stack;
use App\Project;
use App\Database;
use Tests\TestCase;
use App\Environment;
use App\SourceProvider;
use Illuminate\Support\Facades\Bus;
use App\Jobs\CreateLoadBalancerIfNecessary;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_stacks_can_be_listed()
    {
        $stack = factory(Stack::class)->create();
        $project = $stack->project();
        $project->environments()->save($environment2 = factory(Environment::class)->make());
        $environment2->stacks()->save(factory(Stack::class)->make());

        $response = $this->actingAs($stack->environment->project->user, 'api')->get(
            '/api/project/'.$stack->environment->project->id.'/stacks'
        );

        $this->assertCount(2, $response->original);
    }


    public function test_404_returned_if_environment_doesnt_exist()
    {
        Bus::fake();

        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json('POST', '/api/stack/24820439', [
            'source_provider_id' => 'github',
            'name' => 'test-stack',
            'repository' => 'laravel/laravel',
            'branch' => 'master',
            'databases' => ['mysql'],
            'web' => [
                'size' => '2GB',
                'serves' => ['laravel.com'],
                'scale' => 2,
            ],
            'worker' => [
                'size' => '2GB',
                'scale' => 2,
                'daemons' => [
                    'first' => [
                        'command' => 'php artisan horizon',
                        'processes' => 1,
                        'wait' => 60,
                    ],
                ],
            ],
            'build' => [
                'composer install -o',
                'php artisan migrate',
                'php artisan route:cache',
                'php artisan config:cache',
            ],
        ]);

        $response->assertStatus(404);

        Bus::assertNotDispatched(CreateLoadBalancerIfNecessary::class);
    }


    public function test_validation_fails_if_no_web_servers_present()
    {
        Bus::fake();

        $environment = factory(Environment::class)->create();
        $environment->project->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());
        $environment->project->databases()->save(factory(Database::class)->make(['name' => 'mysql']));

        $response = $this->withExceptionHandling()->actingAs($environment->project->user, 'api')->json('POST', '/api/environment/'.$environment->id.'/stack', [
            'source_provider_id' => $source->name,
            'name' => 'test-stack',
            'repository' => 'laravel/laravel',
            'branch' => 'master',
            'databases' => ['mysql'],
            'worker' => [
                'size' => '2GB',
                'scale' => 2,
                'daemons' => [
                    'first' => [
                        'command' => 'php artisan horizon',
                        'processes' => 1,
                        'wait' => 60,
                    ],
                ],
            ],
            'build' => [
                'composer install -o',
                'php artisan migrate',
                'php artisan route:cache',
                'php artisan config:cache',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals('At least one web server must be defined.', $response->original['errors']['web'][0]);

        Bus::assertNotDispatched(CreateLoadBalancerIfNecessary::class);
    }


    public function test_validation_fails_if_app_servers_combined_with_other_servers()
    {
        Bus::fake();

        $environment = factory(Environment::class)->create();
        $environment->project->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());

        $response = $this->withExceptionHandling()->actingAs($environment->project->user, 'api')->json('POST', '/api/environment/'.$environment->id.'/stack', [
            'source_provider_id' => $source->name,
            'repository' => 'laravel/laravel',
            'branch' => 'master',
            'databases' => ['mysql'],
            'app' => [
                'size' => '2GB',
                'serves' => ['laravel.com'],
                'scale' => 1,
            ],
            'worker' => [
                'size' => '2GB',
                'scale' => 2,
                'daemons' => [
                    'first' => [
                        'command' => 'php artisan horizon',
                        'processes' => 1,
                        'wait' => 60,
                    ],
                ],
            ],
            'build' => [
                'composer install -o',
                'php artisan migrate',
                'php artisan route:cache',
                'php artisan config:cache',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertEquals('App servers may not be provisioned with web and worker servers.', $response->original['errors']['app'][0]);

        Bus::assertNotDispatched(CreateLoadBalancerIfNecessary::class);
    }


    public function test_stacks_can_be_provisioned()
    {
        Bus::fake();

        $environment = factory(Environment::class)->create();
        $project = $environment->project;
        $environment->project->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());
        $environment->project->databases()->save(factory(Database::class)->make(['name' => 'mysql']));

        $response = $this->actingAs($environment->project->user, 'api')->json('POST', '/api/environment/'.$environment->id.'/stack', [
            'source_provider_id' => $source->name,
            'name' => 'test-stack',
            'repository' => 'laravel/laravel',
            'branch' => 'master',
            'databases' => ['mysql'],
            'web' => [
                'size' => '2GB',
                'tls' => 'self-signed',
                'serves' => ['laravel.com'],
                'scale' => 2,
                'scripts' => [
                    'exit 1',
                ],
            ],
            'worker' => [
                'size' => '2GB',
                'scale' => 2,
                'daemons' => [
                    'first' => [
                        'command' => 'php artisan horizon',
                        'processes' => 1,
                        'wait' => 60,
                    ],
                ],
            ],
            'build' => [
                'composer install -o',
                'php artisan migrate',
                'php artisan route:cache',
                'php artisan config:cache',
            ],
        ]);

        $response->assertStatus(201);
        Bus::assertDispatched(CreateLoadBalancerIfNecessary::class);

        $environment = $environment->fresh();

        // Stack assertions...
        $stack = $environment->stacks->first();

        $this->assertEquals($environment->project->user->id, $stack->creator->id);
        $this->assertCount(1, $stack->databases);
        $this->assertEquals('mysql', $stack->databases->first()->name);

        $this->assertCount(2, $stack->webServers);
        $this->assertEquals('exit 1', $stack->meta['scripts']['web'][0]);
        $this->assertEquals($stack->name.'-web-1', $stack->webServers->first()->name);
        $this->assertEquals(['laravel.com'], $stack->webServers->first()->meta['serves']);
        $this->assertEquals('self-signed', $stack->webServers->first()->meta['tls']);

        $this->assertCount(2, $stack->workerServers);
        $this->assertEquals('php artisan horizon', $stack->meta['initial_daemons']['first']['command']);

        $this->assertEquals('provisioning', $stack->status);
    }


    public function test_stacks_can_be_provisioned_with_app_servers()
    {
        Bus::fake();

        $environment = factory(Environment::class)->create();
        $project = $environment->project;
        $project->user->sourceProviders()->save($source = factory(SourceProvider::class)->make());
        $project->databases()->save(factory(Database::class)->make(['name' => 'mysql']));

        $response = $this->actingAs($environment->project->user, 'api')->json('POST', '/api/environment/'.$environment->id.'/stack', [
            'source_provider_id' => $source->name,
            'name' => 'test-stack',
            'name' => 'test-stack',
            'repository' => 'laravel/laravel',
            'branch' => 'master',
            'databases' => ['mysql'],
            'app' => [
                'size' => '2GB',
                'serves' => ['laravel.com'],
                'daemons' => [
                    'first' => [
                        'command' => 'php artisan horizon',
                        'processes' => 1,
                        'wait' => 60,
                    ],
                ],
            ],
            'build' => [
                'composer install -o',
                'php artisan migrate',
                'php artisan route:cache',
                'php artisan config:cache',
            ],
        ]);

        $response->assertStatus(201);
        Bus::assertDispatched(CreateLoadBalancerIfNecessary::class);

        $environment = $environment->fresh();

        // Environment assertions...
        $this->assertEquals('production', $environment->name);
        $this->assertCount(1, $environment->stacks);
        $this->assertEquals('DigitalOcean', $environment->project->serverProvider->name);

        // Stack assertions...
        $stack = $environment->stacks->first();

        $this->assertCount(1, $stack->appServers);
        $this->assertEquals($stack->name.'-app-1', $stack->appServers->first()->name);
        $this->assertEquals(['laravel.com'], $stack->appServers->first()->meta['serves']);
        $this->assertEquals('php artisan horizon', $stack->meta['initial_daemons']['first']['command']);

        $this->assertEquals('provisioning', $stack->status);
    }


    public function test_stacks_may_be_deleted()
    {
        $stack = factory(Stack::class)->create();

        $response = $this->actingAs($stack->environment->project->user, 'api')->json(
            'delete', '/api/stacks/'.$stack->id
        );

        $response->assertStatus(200);

        $this->assertCount(0, Stack::all());
    }


    public function test_stacks_cant_be_deleted_without_permission()
    {
        $stack = factory(Stack::class)->create();

        $user = $this->user();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'delete', '/api/stacks/'.$stack->id
        );

        $response->assertStatus(403);
    }


    public function test_stacks_can_always_be_deleted_by_the_user_that_created_them()
    {
        $stack = factory(Stack::class)->create();

        $user = $this->user();
        $stack->update(['creator_id' => $user->id]);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'delete', '/api/stacks/'.$stack->id
        );

        $response->assertStatus(200);
    }


    public function test_stacks_may_not_be_deleted_while_deploying()
    {
        $stack = factory(Stack::class)->create();
        $stack->markAsDeploying();

        $response = $this->withExceptionHandling()->actingAs($stack->environment->project->user, 'api')->json(
            'delete', '/api/stacks/'.$stack->id
        );

        $response->assertStatus(422);
    }
}
