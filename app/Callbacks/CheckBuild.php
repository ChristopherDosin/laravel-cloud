<?php

namespace App\Callbacks;

use App\Task;
use App\ServerDeployment;

class CheckBuild
{
    /**
     * The server deployment ID.
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
        if ($deployment = ServerDeployment::find($this->id)) {
            $task->successful()
                    ? $deployment->markAsBuilt()
                    : $deployment->markAsFailed();
        }
    }
}
