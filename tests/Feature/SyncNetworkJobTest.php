<?php

namespace Tests\Feature;

use App\Stack;
use App\Database;
use App\AppServer;
use App\WebServer;
use App\IpAddress;
use Tests\TestCase;
use App\Jobs\SyncNetwork;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncNetworkJobTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_task_id_is_stored()
    {
        $database = factory(Database::class)->create();
        $database->networkLock()->release();

        $appServer = factory(AppServer::class)->create();
        $appServer->address()->save(factory(IpAddress::class)->make());
        $appServer = $appServer->fresh();

        $webServer = factory(WebServer::class)->create(['stack_id' => $appServer->stack->id]);
        $webServer->address()->save(factory(IpAddress::class)->make());
        $webServer = $webServer->fresh();

        $database->stacks()->sync([$appServer->stack->id]);

        $job = new SyncNetworkFakeJob($database);
        $job->handle();

        $database->networkLock()->release();

        $this->assertEquals([
            $appServer->address->public_address,
            $appServer->address->private_address,
            $webServer->address->public_address,
            $webServer->address->private_address,
        ], $job->ipAddresses);

        $this->assertEquals(
            $job->ipAddresses, $database->fresh()->allows_access_from
        );
    }


    public function test_job_is_released_if_database_is_not_provisioned()
    {
        $database = factory(Database::class)->create([
            'status' => 'provisioning',
        ]);
        $database->networkLock()->release();

        $job = new SyncNetworkFakeJob($database);
        $job->handle();

        $database->networkLock()->release();

        $this->assertEquals(15, $job->released);
    }


    public function test_job_is_released_if_no_lock_can_be_acquired()
    {
        $database = factory(Database::class)->create([
            'status' => 'provisioned',
        ]);
        $database->networkLock()->get();

        $job = new SyncNetworkFakeJob($database);
        $job->handle();

        $database->networkLock()->release();

        $this->assertEquals(15, $job->released);
    }
}


class SyncNetworkFakeJob extends SyncNetwork
{
    public $database;
    public $ipAddresses;
    public $released;
    public $deleted = false;

    protected function sync(Database $database)
    {
        $this->database = $database;
        $this->ipAddresses = $database->shouldAllowAccessFrom();

        return parent::sync($database);
    }

    public function release($delay = 0)
    {
        $this->released = $delay;
    }

    public function delete()
    {
        $this->deleted = true;
    }
}
