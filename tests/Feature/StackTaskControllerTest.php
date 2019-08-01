<?php

namespace Tests\Feature;

use App\User;
use App\Stack;
use App\Project;
use Tests\TestCase;
use App\Environment;
use App\Jobs\RunStackTask;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackTaskControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_stack_tasks_can_be_created()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $response = $this->actingAs($stack->environment->project->user, 'api')->json(
            'post', '/api/stack/'.$stack->id.'/stack-tasks', [
                'name' => 'Some Task',
                'user' => 'root',
                'commands' => [
                    'exit 1',
                ],
            ]
        );

        $response->assertStatus(201);

        Bus::assertDispatched(RunStackTask::class, function ($job) use ($response) {
            return $job->task->id === $response->original->id;
        });
    }


    public function test_user_with_access_may_run_tasks()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $user = $this->user();
        $stack->environment->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'post', '/api/stack/'.$stack->id.'/stack-tasks', [
                'name' => 'Some Task',
                'user' => 'cloud',
                'commands' => [
                    'exit 1',
                ],
            ]
        );

        $response->assertStatus(201);

        Bus::assertDispatched(RunStackTask::class);
    }


    public function test_user_may_not_run_tasks_without_ssh_permission()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $user = $this->user();
        $stack->environment->project->shareWith($user);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'post', '/api/stack/'.$stack->id.'/stack-tasks', [
                'name' => 'Some Task',
                'user' => 'root',
                'commands' => [
                    'exit 1',
                ],
            ]
        );

        $response->assertStatus(403);

        Bus::assertNotDispatched(RunStackTask::class);
    }


    public function test_users_with_ssh_access_still_cant_run_as_root()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $user = $this->user();
        $stack->environment->project->shareWith($user, ['ssh:server']);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')->json(
            'post', '/api/stack/'.$stack->id.'/stack-tasks', [
                'name' => 'Some Task',
                'user' => 'root',
                'commands' => [
                    'exit 1',
                ],
            ]
        );

        $response->assertStatus(403);

        Bus::assertNotDispatched(RunStackTask::class);
    }
}
