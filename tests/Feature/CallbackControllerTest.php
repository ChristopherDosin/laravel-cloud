<?php

namespace Tests\Feature;

use App\Task;
use Tests\TestCase;
use Facades\App\ShellProcessRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CallbackControllerTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_task_status_is_updated()
    {
        $task = factory(Task::class)->create(['status' => 'running']);

        ShellProcessRunner::shouldReceive('run')->andReturn((object) [
            'exitCode' => 0,
            'output' => 'output',
            'timedOut' => false,
        ]);

        $response = $this->get('/api/callback/'.hashid_encode($task->id));

        $response->assertStatus(200);
        $task = $task->fresh();
        $this->assertEquals(0, $task->exit_code);
        $this->assertEquals('finished', $task->status);
    }


    public function test_404_is_returned_for_tasks_that_dont_exist()
    {
        $response = $this->withExceptionHandling()->get('/api/callback/no');
        $response->assertStatus(404);

        $response = $this->withExceptionHandling()->get('/api/callback/-1');
        $response->assertStatus(404);

        $response = $this->withExceptionHandling()->get('/api/callback/alsdkjadf10390');
        $response->assertStatus(404);
    }


    public function test_task_is_updated_with_exit_code_from_query_string()
    {
        $task = factory(Task::class)->create(['status' => 'running']);

        ShellProcessRunner::shouldReceive('run')->andReturn((object) [
            'exitCode' => 0,
            'output' => 'output',
            'timedOut' => false,
        ]);

        $response = $this->get('/api/callback/'.hashid_encode($task->id).'?exit_code=1');

        $response->assertStatus(200);
        $task = $task->fresh();
        $this->assertEquals(1, $task->exit_code);
        $this->assertEquals('finished', $task->status);
    }


    public function test_callbacks_are_executed()
    {
        TestCallbackHandler::$called = false;

        $task = factory(Task::class)->create([
            'status' => 'running',
            'options' => ['then' => [TestCallbackHandler::class]],
        ]);

        ShellProcessRunner::shouldReceive('run')->andReturn((object) [
            'exitCode' => 0,
            'output' => 'output',
            'timedOut' => false,
        ]);

        $response = $this->get('/api/callback/'.hashid_encode($task->id));

        $response->assertStatus(200);
        $task = $task->fresh();
        $this->assertEquals('finished', $task->status);
        $this->assertTrue(TestCallbackHandler::$called);
    }
}


class TestCallbackHandler
{
    public static $called = false;

    public function handle(Task $task)
    {
        static::$called = true;
    }
}
