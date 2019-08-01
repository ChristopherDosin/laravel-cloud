<?php

namespace Tests\Feature;

use App\Stack;
use App\Database;
use App\AppServer;
use App\IpAddress;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_network_is_synced_reports_true_if_allows_access_from_all_stacks()
    {
        $appServer = factory(AppServer::class)->create();
        $appServer->address()->save(factory(IpAddress::class)->make());
        $database = factory(Database::class)->create();

        $database->stacks()->sync([$appServer->stack->id]);

        $database->update([
            'allows_access_from' => [
                $appServer->address->public_address,
                $appServer->address->private_address
            ],
        ]);

        $this->assertTrue($database->networkIsSynced());
    }



    public function test_network_is_synced_reports_false_if_doesnt_allow_access_from_all_stacks()
    {
        $appServer = factory(AppServer::class)->create();
        $appServer->address()->save(factory(IpAddress::class)->make());
        $database = factory(Database::class)->create();

        $database->stacks()->sync([$appServer->stack->id]);

        $this->assertFalse($database->networkIsSynced());
    }
}
