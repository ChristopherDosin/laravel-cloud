<?php

namespace App\Jobs;

use App\Deployment;
use App\Scripts\PauseDaemons as PauseDaemonsScript;

class PauseDaemons extends ManipulatesDaemons
{
    /**
     * Get the script instance for the job.
     *
     * @return \App\Scripts\Script
     */
    public function script()
    {
        return new PauseDaemonsScript($this->deployment);
    }
}
