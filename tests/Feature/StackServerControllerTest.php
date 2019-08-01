<?php

namespace Tests\Feature;

use App\Stack;
use App\AppServer;
use App\IpAddress;
use App\WebServer;
use Tests\TestCase;
use App\WorkerServer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackServerControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_all_servers_are_returned()
    {
        $stack = factory(Stack::class)->create();
        $stack->appServers()->save($appServer = factory(AppServer::class)->make());
        $stack->appServers()->save($appServer2 = factory(AppServer::class)->make());
        $stack->webServers()->save($webServer = factory(WebServer::class)->make());
        $stack->workerServers()->save($workerServer = factory(WorkerServer::class)->make());
        $workerServer->address()->save($address = factory(IpAddress::class)->make());

        $response = $this->actingAs(
            $stack->environment->project->user, 'api'
        )->get("/api/stack/{$stack->id}/servers");

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->original['app']));
        $this->assertEquals(1, count($response->original['web']));
        $this->assertEquals(1, count($response->original['worker']));
        $this->assertEquals($address->public_address, $response->original['worker'][0]['address']['public_address']);
    }
}
