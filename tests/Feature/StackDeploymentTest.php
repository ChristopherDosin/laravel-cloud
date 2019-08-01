<?php

namespace Tests\Feature;

use App\Stack;
use App\AppServer;
use App\Deployment;
use Tests\TestCase;
use App\Jobs\Build;
use App\Jobs\MonitorDeployment;
use Illuminate\Support\Facades\Bus;
use App\Jobs\TimeOutDeploymentIfStillRunning;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackDeploymentTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_deploy_dispatches_proper_jobs_and_creates_deployment_record()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);
        $stack->appServers()->save($server = factory(AppServer::class)->create());

        $stack->deploymentLock()->release();

        $deployment = $stack->deploy('hash', ['first'], ['second'], ['storage'], [
            'first' => [
                'connection' => 'redis',
            ],
        ]);

        $this->assertEquals('hash', $deployment->commit_hash);
        $this->assertEquals(['first'], $deployment->build_commands);
        $this->assertEquals(['second'], $deployment->activation_commands);
        $this->assertEquals('building', $deployment->status);

        Bus::assertDispatched(MonitorDeployment::class, function ($job) use ($deployment) {
            return $job->deployment->id === $deployment->id;
        });

        Bus::assertDispatched(TimeOutDeploymentIfStillRunning::class, function ($job) use ($deployment) {
            return $job->deployment->id === $deployment->id;
        });

        Bus::assertDispatched(Build::class, function ($job) use ($server) {
            return $job->deployment->deployable->id === $server->id;
        });

        $stack->deploymentLock()->release();
    }


    public function test_deploy_can_be_performed_by_branch()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);
        $stack->appServers()->save($server = factory(AppServer::class)->create());

        $stack->deploymentLock()->release();

        $deployment = $stack->deployBranch('master', ['first'], ['second'], ['storage'], [
            'first' => [
                'connection' => 'redis',
            ],
        ]);

        $this->assertEquals('master', $deployment->branch);
        $this->assertNotNull('hash', $deployment->commit_hash);

        $stack->deploymentLock()->release();
    }


    public function test_previous_deployments_are_trimmed()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create([
            'status' => 'provisioned',
        ]);

        $stack->deploymentLock()->release();

        $stack->appServers()->save($server = factory(AppServer::class)->create());

        for ($i = 0; $i < 30; $i++) {
            $stack->deployments()->save(factory(Deployment::class)->make());
        }

        $this->assertCount(30, Deployment::all());
        $deployment = $stack->deploy('master', ['first'], ['second']);
        $this->assertCount(20, Deployment::all());

        $stack->deploymentLock()->release();
    }
}
