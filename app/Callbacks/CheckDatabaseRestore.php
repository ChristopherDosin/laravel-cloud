<?php

namespace App\Callbacks;

use App\Task;
use App\DatabaseRestore;

class CheckDatabaseRestore
{
    /**
     * The database backup ID.
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
        if ($restore = DatabaseRestore::find($this->id)) {
            $task->successful()
                    ? $restore->markAsFinished($task->output)
                    : $restore->markAsFailed($task->exit_code, $task->output);
        }
    }
}
