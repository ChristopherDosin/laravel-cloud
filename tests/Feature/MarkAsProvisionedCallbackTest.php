<?php

namespace Tests\Feature;

use App\Task;
use App\Database;
use Tests\TestCase;
use App\Callbacks\MarkAsProvisioned;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarkAsProvisionedCallbackTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_provisionable_is_marked_as_provisioned()
    {
        $database = factory(Database::class)->create([
            'status' => 'provisioning'
        ]);

        $database->tasks()->save($task = factory(Task::class)->create());

        $handler = new MarkAsProvisioned;
        $handler->handle($task);

        $this->assertEquals('provisioned', $database->fresh()->status);
    }


    public function test_can_be_called_for_models_that_dont_exist_without_errors()
    {
        $task = factory(Task::class)->create([
            'options' => ['type' => Database::class, 'id' => 1000],
        ]);

        $handler = new MarkAsProvisioned;
        $handler->handle($task);

        $this->assertTrue(true);
    }
}
