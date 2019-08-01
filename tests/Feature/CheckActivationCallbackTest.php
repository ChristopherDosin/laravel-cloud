<?php

namespace Tests\Feature;

use App\Task;
use Tests\TestCase;
use App\ServerDeployment;
use App\Callbacks\CheckActivation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckActivationCallbackTest extends TestCase
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

        $handler = new CheckActivation($deployment->id);
        $handler->handle($task);

        $this->assertEquals('activated', $deployment->fresh()->status);
    }


    public function test_deployment_status_is_properly_updated_if_failed()
    {
        $deployment = factory(ServerDeployment::class)->create();

        $task = factory(Task::class)->create([
            'exit_code' => 1,
        ]);

        $handler = new CheckActivation($deployment->id);
        $handler->handle($task);

        $this->assertEquals('failed', $deployment->fresh()->status);
    }
}
