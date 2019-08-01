<?php

namespace App\Jobs;

use App\WorkerServer;

class ProvisionWorkerServer extends ServerProvisioner
{
    /**
     * Create a new job instance.
     *
     * @param  \App\WorkerServer  $provisionable
     * @return void
     */
    public function __construct(WorkerServer $provisionable)
    {
        $this->provisionable = $provisionable;
    }
}
