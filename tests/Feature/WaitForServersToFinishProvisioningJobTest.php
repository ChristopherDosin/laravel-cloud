<?php

namespace Tests\Feature;

use App\Stack;
use App\Balancer;
use App\AppServer;
use Carbon\Carbon;
use Tests\TestCase;
use App\Jobs\WaitForServersToFinishProvisioning;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaitForServersToFinishProvisioningJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_job_is_not_deleted_when_stack_is_provisioned_but_balancer_isnt_finished()
    {
        $stack = factory(Stack::class)->create();
        $stack->project()->balancers()->save(factory(Balancer::class)->make([
            'status' => 'provisioning',
        ]));
        $job = new WaitForServersToFinishProvisioningFakeJob($stack);
        $job->handle();

        $this->assertFalse($job->deleted);
    }


    public function test_job_is_deleted_when_stack_is_provisioned_and_no_balancers()
    {
        $stack = factory(Stack::class)->create(['initial_server_count' => 1]);
        $stack->appServers()->save(factory(AppServer::class)->make([
            'status' => 'provisioned',
        ]));
        $job = new WaitForServersToFinishProvisioningFakeJob($stack);
        $job->handle();

        $this->assertTrue($job->deleted);
    }


    public function test_job_is_deleted_when_stack_is_provisioned_and_has_provisioned_balancer()
    {
        $stack = factory(Stack::class)->create(['initial_server_count' => 1]);
        $stack->appServers()->save(factory(AppServer::class)->make([
            'status' => 'provisioned',
        ]));
        $stack->environment->project->balancers()->save($balancer = factory(Balancer::class)->make());
        $balancer->markAsProvisioned();
        $job = new WaitForServersToFinishProvisioningFakeJob($stack);
        $job->handle();

        $this->assertTrue($job->deleted);
    }


    public function test_job_fails_when_stack_is_old()
    {
        $stack = factory(Stack::class)->create(['created_at' => Carbon::now()->subDays(10)]);
        $stack->appServers()->save(factory(AppServer::class)->create(['status' => 'pending']));
        $job = new WaitForServersToFinishProvisioningFakeJob($stack);
        $job->handle();

        $this->assertNotNull($job->exception);
    }


    public function test_job_fails_when_no_app_or_web_servers()
    {
        $stack = factory(Stack::class)->create(['initial_server_count' => 3]);
        $job = new WaitForServersToFinishProvisioningFakeJob($stack);
        $job->handle();

        $this->assertNotNull($job->exception);
    }
}


class WaitForServersToFinishProvisioningFakeJob extends WaitForServersToFinishProvisioning
{
    public $deleted = false;
    public $exception;

    public function delete()
    {
        $this->deleted = true;
    }

    public function fail($exception = null)
    {
        $this->exception = $exception;
    }
}
