<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use App\Deployment;
use Tests\TestCase;
use App\ServerDeployment;
use App\Jobs\StopScheduler;
use App\Jobs\StartScheduler;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchedulerControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_scheduler_can_be_started()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $stack->deployments()->save(factory(Deployment::class)->make());

        $stack->deployments()->save($lastDeployment = factory(Deployment::class)->make([
            'schedule' => ['first']
        ]));

        $lastDeployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $response = $this->actingAs($stack->project()->user, 'api')
                    ->json('post', '/api/stack/'.$stack->id.'/scheduler');

        $response->assertStatus(201);

        Bus::assertDispatched(StartScheduler::class, function ($job) use ($serverDeployment) {
            return $serverDeployment->id === $job->deployment->id;
        });
    }


    public function test_scheduler_can_be_stopped()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $stack->deployments()->save(factory(Deployment::class)->make());

        $stack->deployments()->save($lastDeployment = factory(Deployment::class)->make([
            'schedule' => ['first']
        ]));

        $lastDeployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $response = $this->actingAs($stack->project()->user, 'api')
                    ->json('delete', '/api/stack/'.$stack->id.'/scheduler');

        $response->assertStatus(200);

        Bus::assertDispatched(StopScheduler::class, function ($job) use ($serverDeployment) {
            return $serverDeployment->id === $job->deployment->id;
        });
    }
}
