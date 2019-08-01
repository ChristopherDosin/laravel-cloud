<?php

namespace Tests\Feature;

use App\Task;
use App\Database;
use Carbon\Carbon;
use Tests\TestCase;
use App\Scripts\Sleep;
use App\Scripts\WriteDummyFile;
use App\Scripts\GetCurrentDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskTest extends TestCase
{
    use RefreshDatabase;


    public function test_scripts_can_be_run_in_foreground()
    {
        $database = factory(Database::class)->create([
            'port' => 2288,
        ]);

        $task = $database->run(new GetCurrentDirectory);

        $this->assertEquals('finished', $task->status);
        $this->assertEquals(0, $task->exit_code);
        $this->assertEquals('/root', $task->output);
    }


    public function test_scripts_can_be_run_in_background()
    {
        $database = factory(Database::class)->create([
            'port' => 2288,
        ]);

        $task = $database->runInBackground(new WriteDummyFile);

        sleep(2);

        $output = $task->retrieveOutput('/root/dummy');

        $this->assertEquals('Hello World', $output);
    }


    public function test_scripts_can_timeout()
    {
        $database = factory(Database::class)->create([
            'port' => 2288,
        ]);

        $task = $database->run(new Sleep, ['timeout' => 3]);

        $this->assertEquals('timeout', $task->status);
        $this->assertNotEquals(0, $task->exit_code);
        $this->assertEquals('', $task->output);
    }


    public function test_tasks_can_be_pruned()
    {
        $task1 = factory(Task::class)->create([
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $task2 = factory(Task::class)->create([
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $task3 = factory(Task::class)->create([
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $this->assertEquals(2, Task::prune(Carbon::now()->subDays(2), 1));
        $this->assertEquals(1, Task::count());
        $this->assertEquals($task1->id, Task::all()->first()->id);
    }
}
