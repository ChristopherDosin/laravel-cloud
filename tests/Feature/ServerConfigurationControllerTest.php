<?php

namespace Tests\Feature;

use App\Stack;
use App\Project;
use Tests\TestCase;
use App\Jobs\SyncServers;
use Illuminate\Support\Facades\Bus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ServerConfigurationControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_stack_server_configurations_can_be_rebuilt()
    {
        Bus::fake();

        $stack = factory(Stack::class)->create();

        $response = $this->actingAs($stack->project()->user, 'api')
                    ->json('PUT', '/api/stack/'.$stack->id.'/server-configuration');

        $response->assertStatus(200);

        Bus::assertDispatched(SyncServers::class, function ($job) use ($stack) {
            return $job->stack->id === $stack->id;
        });
    }
}
