<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use App\Deployment;
use Tests\TestCase;
use App\ServerDeployment;
use App\Jobs\PauseDaemons;
use App\Jobs\UnpauseDaemons;
use App\Jobs\RestartDaemons;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DaemonControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    /**
     * @dataProvider modifierProvider
     */
    public function test_daemons_can_be_modified($action, $job, $status)
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->deployments()->save(factory(Deployment::class)->make());

        $stack->deployments()->save($lastDeployment = factory(Deployment::class)->make([
            'daemons' => ['first']
        ]));

        $lastDeployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $response = $this->actingAs($stack->project()->user, 'api')
                    ->json('put', '/api/stack/'.$stack->id.'/daemons', [
                        'action' => $action,
                    ]);

        $response->assertStatus(200);

        $this->assertEquals($status, $serverDeployment->deployable->daemon_status);

        Bus::assertDispatched($job, function ($job) use ($serverDeployment) {
            return $job->deployment->id === $serverDeployment->id;
        });
    }


    /**
     * @dataProvider modifierProvider
     */
    public function test_nothing_dispatched_if_no_daemons($action, $job)
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();
        $stack->deployments()->save(factory(Deployment::class)->make());
        $stack->deployments()->save($lastDeployment = factory(Deployment::class)->make());

        $response = $this->actingAs($stack->project()->user, 'api')
                    ->json('put', '/api/stack/'.$stack->id.'/daemons', [
                        'action' => $action,
                    ]);

        $response->assertStatus(200);

        Bus::assertNotDispatched($job, function ($job) use ($lastDeployment) {
            return $job->deployment->id === $lastDeployment->id;
        });
    }


    public function test_cant_modify_daemons_if_the_stack_has_no_deployments()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $response = $this->withExceptionHandling()->actingAs($stack->project()->user, 'api')
                    ->json('put', '/api/stack/'.$stack->id.'/daemons', [
                        'action' => 'start',
                    ]);

        $response->assertStatus(422);
    }


    public function modifierProvider()
    {
        return [
            ['start', RestartDaemons::class, 'running'],
            ['restart', RestartDaemons::class, 'running'],
            ['pause', PauseDaemons::class, 'pending'],
            ['unpause', UnpauseDaemons::class, 'running'],
        ];
    }
}
