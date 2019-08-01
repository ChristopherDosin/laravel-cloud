<?php

namespace App\Jobs;

use App\Deployment;
use App\Scripts\StartDaemons as StartDaemonsScript;

class StartDaemons extends ManipulatesDaemons
{
    /**
     * Get the script instance for the job.
     *
     * @return \App\Scripts\Script
     */
    public function script()
    {
        return new StartDaemonsScript($this->deployment);
    }
}
