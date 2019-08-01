<?php

namespace Tests\Feature;

use App\User;
use App\Stack;
use App\WebServer;
use Tests\TestCase;
use App\Environment;
use App\Jobs\PromoteStack;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromotedStackControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_stacks_can_be_promoted()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $response = $this->actingAs($stack->project()->user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
            ]);

        $response->assertStatus(200);

        Bus::assertDispatched(PromoteStack::class, function ($job) use ($stack) {
            return $job->stack->id === $stack->id;
        });

        $stack->environment->promotionLock()->release();
    }


    public function test_stacks_cant_be_promoted_if_not_serving()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->webServers()->save(factory(WebServer::class)->make());

        $response = $this->withExceptionHandling()->actingAs($stack->project()->user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
            ]);

        $response->assertStatus(422);

        $stack->environment->promotionLock()->release();
    }


    public function test_stacks_can_be_promoted_and_daemons_will_wait()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $stack->environment->promotionLock()->release();

        $response = $this->actingAs($stack->project()->user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
                'wait' => true,
            ]);

        $response->assertStatus(200);

        Bus::assertDispatched(PromoteStack::class, function ($job) use ($stack) {
            return $job->stack->id === $stack->id &&
                   $job->options['wait'] === true;
        });

        $stack->environment->promotionLock()->release();
    }


    public function test_stack_cant_be_promoted_if_locked()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $stack->environment->promotionLock()->get();

        $response = $this->actingAs($stack->project()->user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
            ]);

        $response->assertStatus(409);

        $stack->environment->promotionLock()->release();
    }


    public function test_stacks_cant_be_promoted_if_not_promotable()
    {
        $stack = factory(Stack::class)->create();

        $response = $this->withExceptionHandling()->actingAs($stack->project()->user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
            ]);

        $response->assertStatus(422);

        $stack->environment->promotionLock()->release();
    }


    public function test_collaborator_can_promote_stacks()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $user = factory(User::class)->create();

        $stack->project()->shareWith($user);

        $stack->webServers()->save(factory(WebServer::class)->make([
            'meta' => ['serves' => ['laravel.com']],
        ]));

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
            ->json('PUT', '/api/environment/'.$stack->environment_id.'/promoted-stack', [
                'stack' => $stack->id,
            ]);

        $response->assertStatus(200);

        $stack->environment->promotionLock()->release();
    }
}
