<?php

namespace Tests\Feature;

use App\Stack;
use App\StackTask;
use App\WebServer;
use App\ServerTask;
use Tests\TestCase;
use App\WorkerServer;
use App\ShellProcessRunner;
use App\Events\StackTaskFailed;
use App\Events\ServerTaskFailed;
use App\Events\StackTaskFinished;
use App\Events\ServerTaskFinished;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StackTaskTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_commands_are_distributed_to_appropriate_servers()
    {
        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make());
        $stack->workerServers()->save(factory(WorkerServer::class)->make());

        $task = $stack->tasks()->create([
            'name' => 'Task',
            'user' => 'cloud',
            'commands' => [
                'echo 1',
                'web: echo 2',
                'worker: echo 3',
                'once: echo 4',
            ],
        ]);

        $task->run();

        $serverTasks = $task->serverTasks;

        $this->assertEquals([
            'echo 1',
            'echo 2',
            'echo 4',
        ], $serverTasks[0]->commands);

        $this->assertInstanceOf(WebServer::class, $serverTasks[0]->taskable);

        $this->assertEquals([
            'echo 1',
            'echo 3',
        ], $serverTasks[1]->commands);

        $this->assertInstanceOf(WorkerServer::class, $serverTasks[1]->taskable);
    }


    public function test_server_tasks_are_not_created_if_no_applicable_commands()
    {
        ShellProcessRunner::mock([
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
            (object) ['exitCode' => 0, 'output' => '', 'timedOut' => false],
        ]);

        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make());

        $task = $stack->tasks()->create([
            'name' => 'Task',
            'user' => 'cloud',
            'commands' => [
                'worker: echo 1',
            ],
        ]);

        $task->run();

        $serverTasks = $task->serverTasks;

        $this->assertCount(0, $serverTasks);
    }


    public function test_updating_status_is_properly_synced()
    {
        Event::fake();

        $task = factory(ServerTask::class)->create();
        $task->markAsFinished();

        Event::assertDispatched(ServerTaskFinished::class);
        Event::assertDispatched(StackTaskFinished::class);
    }


    public function test_stack_task_not_marked_as_finished_if_server_tasks_still_running()
    {
        Event::fake();

        $task = factory(StackTask::class)->create();
        $task->serverTasks()->save($serverTask1 = factory(ServerTask::class)->make());
        $task->serverTasks()->save($serverTask2 = factory(ServerTask::class)->make());

        $serverTask1->markAsFinished();

        $this->assertTrue($serverTask1->isFinished());
        Event::assertDispatched(ServerTaskFinished::class);
        Event::assertNotDispatched(StackTaskFinished::class);
    }


    public function test_stack_test_is_updated_if_all_server_tasks_have_failed()
    {
        Event::fake();

        $task = factory(ServerTask::class)->create();
        $task->markAsFailed();

        Event::assertDispatched(ServerTaskFailed::class);
        Event::assertDispatched(StackTaskFailed::class);

        $this->assertTrue($task->hasFailed());
        $this->assertTrue($task->stackTask->hasFailed());
    }


    public function test_tasks_actually_execute()
    {
        $stack = factory(Stack::class)->create();

        $stack->webServers()->save(factory(WebServer::class)->make([
            'port' => 2288,
        ]));
        $stack->workerServers()->save(factory(WorkerServer::class)->make([
            'port' => 2288,
        ]));

        $task = $stack->tasks()->create([
            'name' => 'Task',
            'user' => 'root',
            'commands' => [
                'echo "Hello Stack Test" > /root/stack_test',
            ],
        ]);

        $task->run();

        sleep(2);

        $output = $task->serverTasks[0]->task->retrieveOutput('/root/stack_test');
        $this->assertEquals('Hello Stack Test', $output);

        $output = $task->serverTasks[1]->task->retrieveOutput('/root/stack_test');
        $this->assertEquals('Hello Stack Test', $output);
    }
}
