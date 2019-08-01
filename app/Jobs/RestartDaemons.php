<?php

namespace App\Jobs;

use App\Deployment;
use App\Scripts\RestartDaemons as RestartDaemonsScript;

class RestartDaemons extends ManipulatesDaemons
{
    /**
     * Get the script instance for the job.
     *
     * @return \App\Scripts\Script
     */
    public function script()
    {
        $this->deployment->deployable->createDaemonGeneration();

        return new RestartDaemonsScript($this->deployment->fresh());
    }
}
