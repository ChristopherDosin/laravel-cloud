<?php

namespace Tests\Feature;

use App\Task;
use Tests\TestCase;
use App\Callbacks\Dispatch;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DispatchCallbackTest extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        TestDispatchCallbackJob::$ran = false;
        TestDispatchCallbackJob::$database = null;
    }


    public function test_proper_job_is_dispatched()
    {
        $task = factory(Task::class)->create();

        $this->assertFalse(TestDispatchCallbackJob::$ran);

        $handler = new Dispatch(TestDispatchCallbackJob::class);
        $handler->handle($task);

        $this->assertTrue(TestDispatchCallbackJob::$ran);
        $this->assertEquals($task->provisionable->id, TestDispatchCallbackJob::$database->id);
    }
}


class TestDispatchCallbackJob
{
    public static $database;
    public static $ran = false;

    public function __construct($database)
    {
        static::$database = $database;
    }

    public function handle()
    {
        static::$ran = true;
    }
}
