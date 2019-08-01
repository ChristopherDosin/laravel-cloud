<?php

namespace Tests\Feature;

use Exception;
use Carbon\Carbon;
use Tests\TestCase;
use App\Deployment;
use App\Jobs\Activate;
use App\ServerDeployment;
use App\Jobs\MonitorDeployment;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonitorDeploymentJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_marked_as_finished_if_activated()
    {
        $deployment = factory(Deployment::class)->create();

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'activated',
        ]));

        $job = new FakeMonitorDeploymentJob($deployment);

        $job->handle();

        $this->assertTrue($job->deleted);
        $this->assertTrue($deployment->isFinished());
    }


    public function test_marked_as_failed_if_has_failures()
    {
        $deployment = factory(Deployment::class)->create();

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'failed',
        ]));

        $job = new FakeMonitorDeploymentJob($deployment);

        $job->handle();

        $this->assertTrue($job->deleted);
        $this->assertEquals('failed', $deployment->status);
    }


    public function test_marked_as_timed_out_if_old()
    {
        $deployment = factory(Deployment::class)->create([
            'created_at' => Carbon::now()->subMinutes(40),
        ]);

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'activating',
        ]));

        $job = new FakeMonitorDeploymentJob($deployment);

        $job->handle();

        $this->assertTrue($job->deleted);
        $this->assertEquals('timeout', $deployment->status);
    }


    public function test_activated_if_built()
    {
        Bus::fake();

        $deployment = factory(Deployment::class)->create();

        $deployment->serverDeployments()->save($serverDeployment = factory(ServerDeployment::class)->make([
            'status' => 'built',
        ]));

        $job = new FakeMonitorDeploymentJob($deployment);

        $job->handle();

        $this->assertEquals(5, $job->released);
        $this->assertEquals('activating', $deployment->status);
        $this->assertTrue($deployment->activated);

        Bus::assertDispatched(Activate::class, function ($job) use ($serverDeployment) {
            return $job->deployment->id === $serverDeployment->id;
        });
    }


    public function test_failed_method_marks_as_failed()
    {
        $deployment = factory(Deployment::class)->create();

        $job = new FakeMonitorDeploymentJob($deployment);

        $job->failed(new Exception);

        $this->assertEquals('failed', $deployment->status);
        $this->assertCount(1, $deployment->project()->alerts);
    }
}


class FakeMonitorDeploymentJob extends MonitorDeployment
{
    public $released;
    public $deleted = false;
    public $failed;

    public function release($delay = 0)
    {
        $this->released = $delay;
    }

    public function delete()
    {
        $this->deleted = true;
    }

    public function fail($exception = null)
    {
        $this->failed = $exception;
    }
}
