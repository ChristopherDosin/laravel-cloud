<?php

namespace App\Callbacks;

use App\Task;
use App\ServerDeployment;

class StartBackgroundServices
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
        if (! $deployment = ServerDeployment::find($this->id)) {
            return;
        }

        $task->successful() && $this->shouldStartBackgroundServices($deployment)
                    ? $deployment->deployable->startBackgroundServices()
                    : $deployment->deployable->markDaemonsAsStopped();
    }

    /**
     * Determine if daemons and schedulers should wait to start.
     *
     * @param  \App\ServerDeployment  $deployment
     * @return bool
     */
    protected function shouldStartBackgroundServices(ServerDeployment $deployment)
    {
        return $deployment->deployable->daemonsArePending()
                        ? ! $deployment->isProduction()
                        : $deployment->deployable->daemonsAreRunning();
    }
}
