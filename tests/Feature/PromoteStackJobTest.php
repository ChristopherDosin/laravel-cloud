<?php

namespace Tests\Feature;

use App\Hook;
use App\Stack;
use App\AppServer;
use App\Deployment;
use Tests\TestCase;
use App\ServerDeployment;
use App\Jobs\StopDaemons;
use App\Jobs\PromoteStack;
use App\Jobs\StopScheduler;
use App\Jobs\RestartDaemons;
use App\Jobs\StartScheduler;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromoteStackJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_stack_is_promoted_and_proper_jobs_are_dispatched()
    {
        Bus::fake();

        $previousStack = factory(Stack::class)->create(['promoted' => true]);
        $previousStack->appServers()->save($previousServer = factory(AppServer::class)->make());

        $previousStack->deployments()->save($previousDeployment = factory(Deployment::class)->make([
            'daemons' => ['first'],
            'schedule' => ['first']
        ]));

        $previousDeployment->serverDeployments()->save(
            $previousServerDeployment = factory(ServerDeployment::class)->make([
                'deployable_id' => $previousServer->id,
                'deployable_type' => get_class($previousServer),
            ])
        );

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());

        $stack->deployments()->save($deployment = factory(Deployment::class)->make([
            'daemons' => ['first'],
            'schedule' => ['first']
        ]));

        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make([
                'deployable_id' => $server->id,
                'deployable_type' => get_class($server),
            ])
        );

        $previousStack->update([
            'environment_id' => $stack->environment_id,
        ]);

        $job = new PromoteStack($stack);
        $job->handle();

        Bus::assertDispatched(StartScheduler::class, function ($job) use ($serverDeployment) {
            return $job->deployment->id === $serverDeployment->id;
        });

        Bus::assertDispatched(RestartDaemons::class, function ($job) use ($serverDeployment) {
            return $job->deployment->id === $serverDeployment->id;
        });

        Bus::assertDispatched(StopScheduler::class, function ($job) use ($previousServerDeployment) {
            return $job->deployment->id === $previousServerDeployment->id;
        });

        Bus::assertDispatched(StopDaemons::class, function ($job) use ($previousServerDeployment) {
            return $job->deployment->id === $previousServerDeployment->id;
        });
    }


    public function test_background_services_arent_started_if_instructed_to_wait()
    {
        Bus::fake();

        $previousStack = factory(Stack::class)->create(['promoted' => true]);
        $previousStack->appServers()->save($previousServer = factory(AppServer::class)->make());
        $previousStack->deployments()->save($previousDeployment = factory(Deployment::class)->make([
            'daemons' => ['first']
        ]));
        $previousDeployment->serverDeployments()->save(
            $previousServerDeployment = factory(ServerDeployment::class)->make([
                'deployable_id' => $previousServer->id,
                'deployable_type' => get_class($previousServer),
            ])
        );

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());
        $stack->deployments()->save($deployment = factory(Deployment::class)->make([
            'daemons' => ['first']
        ]));

        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make([
                'deployable_id' => $server->id,
                'deployable_type' => get_class($server),
            ])
        );

        $previousStack->update([
            'environment_id' => $stack->environment_id,
        ]);

        $job = new PromoteStack($stack, ['wait' => true]);
        $job->handle();

        Bus::assertNotDispatched(StartScheduler::class);
        Bus::assertNotDispatched(RestartDaemons::class);

        Bus::assertDispatched(StopDaemons::class, function ($job) use ($previousServerDeployment) {
            return $job->deployment->id === $previousServerDeployment->id;
        });
    }


    public function test_hooks_are_copied_from_previously_promoted_stack_if_instructed()
    {
        Bus::fake();

        $previousStack = factory(Stack::class)->create(['promoted' => true]);
        $previousStack->appServers()->save($previousServer = factory(AppServer::class)->make());
        $previousStack->hooks()->save($hook = factory(Hook::class)->make());

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());

        $previousStack->update([
            'environment_id' => $stack->environment_id,
        ]);

        $job = new PromoteStack($stack, ['wait' => true, 'hooks' => true]);
        $job->handle();

        $this->assertNotEquals($stack->id, $hook->stack_id);
        $this->assertEquals($stack->id, $hook->fresh()->stack_id);
    }


    public function test_hooks_are_not_copied_from_previously_promoted_stack_if_not_instructed()
    {
        Bus::fake();

        $previousStack = factory(Stack::class)->create(['promoted' => true]);
        $previousStack->appServers()->save($previousServer = factory(AppServer::class)->make());
        $previousStack->hooks()->save($hook = factory(Hook::class)->make());

        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($server = factory(AppServer::class)->make());

        $previousStack->update([
            'environment_id' => $stack->environment_id,
        ]);

        $job = new PromoteStack($stack, ['wait' => true, 'hooks' => false]);
        $job->handle();

        $this->assertEquals($previousStack->id, $hook->fresh()->stack_id);
    }
}
