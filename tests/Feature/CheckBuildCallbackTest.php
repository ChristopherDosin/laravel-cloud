<?php

namespace Tests\Feature;

use App\Task;
use Tests\TestCase;
use App\ServerDeployment;
use App\Callbacks\CheckBuild;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckBuildCallbackTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_deployment_status_is_properly_updated_if_successful()
    {
        $deployment = factory(ServerDeployment::class)->create();

        $task = factory(Task::class)->create([
            'exit_code' => 0,
        ]);

        $handler = new CheckBuild($deployment->id);
        $handler->handle($task);

        $this->assertEquals('built', $deployment->fresh()->status);
    }


    public function test_deployment_status_is_properly_updated_if_failed()
    {
        $deployment = factory(ServerDeployment::class)->create();

        $task = factory(Task::class)->create([
            'exit_code' => 1,
        ]);

        $handler = new CheckBuild($deployment->id);
        $handler->handle($task);

        $this->assertEquals('failed', $deployment->fresh()->status);
    }
}
