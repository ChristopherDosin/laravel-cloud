<?php

namespace App\Jobs;

use App\Deployment;
use App\Scripts\StopDaemons as StopDaemonsScript;

class StopDaemons extends ManipulatesDaemons
{
    /**
     * Get the script instance for the job.
     *
     * @return \App\Scripts\Script
     */
    public function script()
    {
        return new StopDaemonsScript($this->deployment);
    }
}
