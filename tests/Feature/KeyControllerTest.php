<?php

namespace Tests\Feature;

use App\User;
use App\Project;
use App\Database;
use App\Balancer;
use App\IpAddress;
use Tests\TestCase;
use App\Jobs\RemoveKeyFromServer;
use Facades\App\ShellProcessRunner;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KeyControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_key_can_be_retrieved()
    {
        Bus::fake();

        $ipAddress = factory(IpAddress::class)->create();

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $response = $this->actingAs($ipAddress->addressable->project->user, 'api')
                    ->json('post', '/api/key/'.$ipAddress->public_address);

        $response->assertStatus(200);

        Bus::assertDispatched(RemoveKeyFromServer::class);
    }


    public function test_key_is_shared_with_authorized_users()
    {
        Bus::fake();

        $user = factory(User::class)->create();
        $ipAddress = factory(IpAddress::class)->create();
        $ipAddress->addressable->project->shareWith($user, ['ssh:database']);

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('post', '/api/key/'.$ipAddress->public_address);

        $response->assertStatus(200);
    }


    public function test_key_is_not_shared_with_unauthorized_users()
    {
        $user = factory(User::class)->create();
        $ipAddress = factory(IpAddress::class)->create();

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('post', '/api/key/'.$ipAddress->public_address);

        $response->assertStatus(403);
    }


    public function test_server_keys_can_be_shared_with_collaborators()
    {
        Bus::fake();

        $user = factory(User::class)->create();
        $ipAddress = factory(IpAddress::class)->create();

        $ipAddress->addressable->project->shareWith($user);

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $response = $this->actingAs($ipAddress->addressable->project->user, 'api')
                    ->json('post', '/api/key/'.$ipAddress->public_address);

        $response->assertStatus(200);

        Bus::assertDispatched(RemoveKeyFromServer::class);
    }


    public function test_balancer_keys_can_be_shared_with_collaborators()
    {
        $user = factory(User::class)->create();
        $server = factory(Balancer::class)->create();

        $server->address()->save($ipAddress = factory(IpAddress::class)->make());

        $server->project->shareWith($user);

        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $response = $this->withExceptionHandling()->actingAs($user, 'api')
                    ->json('post', '/api/key/'.$ipAddress->public_address);

        $response->assertStatus(200);
    }
}
