<?php

namespace App\Callbacks;

use App\Task;
use App\ServerTask;

class CheckServerTask
{
    /**
     * The server task ID.
     *
     * @var int
     */
    public $id;

    /**
     * Create a new callback instance.
     *
     * @param  int  $id
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Handle the callback.
     *
     * @param  Task  $task
     * @return void
     */
    public function handle(Task $task)
    {
        if ($serverTask = ServerTask::find($this->id)) {
            if ($serverTask->task->successful()) {
                $serverTask->markAsFinished();
            } else {
                $serverTask->markAsFailed();
            }
        }
    }
}
