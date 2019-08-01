<?php

namespace Tests\Feature;

use Mockery;
use App\Hook;
use App\Stack;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Facades\App\SourceProviderClientFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HookControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_hook_can_be_created()
    {
        Bus::fake();

        SourceProviderClientFactory::shouldReceive('make')->andReturn(
            $client = Mockery::mock()
        );

        $client->shouldReceive('validRepository')->with(
            'taylorotwell/hello-world', 'master'
        )->andReturn(true);

        $client->shouldReceive('publishHook')->once();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/hook', [
            'name' => 'Test',
            'branch' => 'master',
            'publish' => true,
        ]);

        $response->assertStatus(201);

        $stack = $stack->fresh();
        $hook = $stack->hooks->first();

        $this->assertEquals('taylorotwell/hello-world', $hook->stack->project()->repository);
        $this->assertEquals('master', $hook->branch);
    }


    public function test_hook_can_be_created_without_publishing_to_the_source_control_provider()
    {
        Bus::fake();

        SourceProviderClientFactory::shouldReceive('make')->andReturn(
            $client = Mockery::mock()
        );

        $client->shouldReceive('validRepository')->with(
            'taylorotwell/hello-world', 'master'
        )->andReturn(true);

        $client->shouldReceive('publishHook')->never();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/hook', [
            'name' => 'Test',
            'branch' => 'master',
            'publish' => false,
        ]);

        $response->assertStatus(201);
    }


    public function test_can_not_be_created_with_invalid_branch()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $response = $this->withExceptionHandling()->actingAs(
            $stack->environment->project->user, 'api'
        )->json('post', '/api/stack/'.$stack->id.'/hook', [
            'name' => 'Test',
            'branch' => 'does-not-exist',
            'publish' => true,
        ]);

        $response->assertStatus(422);
    }


    public function test_hook_can_be_deleted()
    {
        Bus::fake();

        $hook = tap(factory(Hook::class)->create([]))->publish();

        $response = $this->actingAs(
            $hook->stack->environment->project->user, 'api'
        )->json('delete', '/api/hook/'.$hook->id);

        $response->assertStatus(200);

        $this->assertCount(0, $hook->stack->fresh()->hooks);
    }


    public function test_cant_delete_the_hook_if_not_a_collaborator()
    {
        Bus::fake();

        $hook = factory(Hook::class)->create();

        $response = $this->withExceptionHandling()->actingAs(
            $this->user(), 'api'
        )->json('delete', '/api/hook/'.$hook->id);

        $response->assertStatus(403);

        $this->assertCount(1, $hook->stack->fresh()->hooks);
    }
}
