<?php

namespace Tests\Feature;

use App\WebServer;
use App\AppServer;
use App\Deployment;
use Tests\TestCase;
use App\WorkerServer;
use App\Jobs\Activate;
use App\ServerDeployment;
use App\Events\DeploymentFailed;
use App\Events\DeploymentFinished;
use App\Events\DeploymentTimedOut;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeploymentTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_deployment_can_determine_if_built()
    {
        $deployment = factory(Deployment::class)->create();
        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $this->assertFalse($deployment->isBuilt());

        $serverDeployment->update(['status' => 'built']);

        $this->assertTrue($deployment->fresh()->isBuilt());
    }


    public function test_deployment_can_determine_if_activated()
    {
        $deployment = factory(Deployment::class)->create();
        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $this->assertFalse($deployment->isActivated());

        $serverDeployment->update(['status' => 'activated']);

        $this->assertTrue($deployment->fresh()->isActivated());
    }


    public function test_activate_method_dispatches_activate_jobs()
    {
        Bus::fake();

        $deployment = factory(Deployment::class)->create();
        $deployment->serverDeployments()->save(
            $serverDeployment = factory(ServerDeployment::class)->make()
        );

        $deployment->activate();

        Bus::assertDispatched(Activate::class, function ($job) use ($serverDeployment) {
            return $job->deployment->id === $serverDeployment->id;
        });
    }


    public function test_build_fans_out_deployment_commands_properly()
    {
        Bus::fake();

        $deployment = factory(Deployment::class)->create([
            'build_commands' => [
                'php artisan all:build',
                'once: php artisan once:build',
                'web: php artisan web:build',
                'worker: php artisan worker:build',
            ],
            'activation_commands' => [
                'php artisan all:activate',
                'once: php artisan once:activate',
                'web: php artisan web:activate',
                'worker: php artisan worker:activate',
            ],
        ]);

        $deployment->stack->webServers()->save($web1 = factory(WebServer::class)->make());
        $deployment->stack->webServers()->save($web2 = factory(WebServer::class)->make());
        $deployment->stack->workerServers()->save($worker1 = factory(WorkerServer::class)->make());

        $deployment->build();

        // Check first web server commands...
        $web1Deployment = ServerDeployment::where('deployable_type', WebServer::class)
                                          ->where('deployable_id', $web1->id)
                                          ->first();

        $this->assertEquals([
            'php artisan all:build',
            'php artisan once:build',
            'php artisan web:build',
        ], $web1Deployment->build_commands);

        $this->assertEquals([
            'php artisan all:activate',
            'php artisan once:activate',
            'php artisan web:activate',
        ], $web1Deployment->activation_commands);

        // Check second web server commands...
        $web2Deployment = ServerDeployment::where('deployable_type', WebServer::class)
                                          ->where('deployable_id', $web2->id)
                                          ->first();

        $this->assertEquals([
            'php artisan all:build',
            'php artisan web:build',
        ], $web2Deployment->build_commands);

        $this->assertEquals([
            'php artisan all:activate',
            'php artisan web:activate',
        ], $web2Deployment->activation_commands);

        // Check worker server commands...
        $worker1Deployment = ServerDeployment::where('deployable_type', WorkerServer::class)
                                          ->where('deployable_id', $worker1->id)
                                          ->first();

        $this->assertEquals([
            'php artisan all:build',
            'php artisan worker:build',
        ], $worker1Deployment->build_commands);

        $this->assertEquals([
            'php artisan all:activate',
            'php artisan worker:activate',
        ], $worker1Deployment->activation_commands);
    }


    public function test_build_fans_out_deployment_commands_properly_with_app_server()
    {
        Bus::fake();

        $deployment = factory(Deployment::class)->create([
            'build_commands' => [
                'php artisan all:build',
                'once: php artisan once:build',
                'php artisan web:build',
                'php artisan worker:build',
            ],
            'activation_commands' => [
                'php artisan all:activate',
                'once: php artisan once:activate',
                'php artisan web:activate',
                'php artisan worker:activate',
            ],
        ]);

        $deployment->stack->appServers()->save($app1 = factory(AppServer::class)->make());

        $deployment->build();

        // Check app server commands...
        $app1Deployment = ServerDeployment::where('deployable_type', AppServer::class)
                                          ->where('deployable_id', $app1->id)
                                          ->first();

        $this->assertEquals([
            'php artisan all:build',
            'php artisan once:build',
            'php artisan web:build',
            'php artisan worker:build',
        ], $app1Deployment->build_commands);

        $this->assertEquals([
            'php artisan all:activate',
            'php artisan once:activate',
            'php artisan web:activate',
            'php artisan worker:activate',
        ], $app1Deployment->activation_commands);
    }


    public function test_can_be_marked_as_finished()
    {
        Event::fake();

        $deployment = factory(Deployment::class)->create();
        $deployment->markAsFinished();

        $this->assertEquals('finished', $deployment->status);

        Event::assertDispatched(DeploymentFinished::class);
    }


    public function test_can_determine_if_activated()
    {
        $deployment = factory(Deployment::class)->create();

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'activated',
        ]));

        $this->assertTrue($deployment->fresh()->isActivated());

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'building',
        ]));

        $this->assertFalse($deployment->fresh()->isActivated());
    }


    public function test_can_be_marked_as_timed_out()
    {
        Event::fake();

        $deployment = factory(Deployment::class)->create();
        $deployment->markAsTimedOut();

        $this->assertEquals('timeout', $deployment->status);

        Event::assertDispatched(DeploymentTimedOut::class);
    }


    public function test_can_determine_if_failures_exist()
    {
        $deployment = factory(Deployment::class)->create();

        $this->assertFalse($deployment->hasFailures());

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'failed',
        ]));

        $this->assertTrue($deployment->fresh()->hasFailures());
    }


    public function test_can_be_marked_as_failed()
    {
        Event::fake();

        $deployment = factory(Deployment::class)->create();
        $deployment->markAsFailed();

        $this->assertEquals('failed', $deployment->status);

        Event::assertDispatched(DeploymentFailed::class);
    }
}
