<?php

namespace App\Callbacks;

use App\Task;

class Dispatch
{
    /**
     * The job that should be dispatched.
     *
     * @var string
     */
    public $class;

    /**
     * Create a new callback instance.
     *
     * @param  string  $class
     * @return void
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Handle the callback.
     *
     * @param  Task  $task
     * @return void
     */
    public function handle(Task $task)
    {
        if ($task->provisionable) {
            $class = $this->class;

            dispatch(new $class($task->provisionable));
        }
    }
}
