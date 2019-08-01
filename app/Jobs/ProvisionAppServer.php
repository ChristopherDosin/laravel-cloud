<?php

namespace App\Jobs;

use App\AppServer;

class ProvisionAppServer extends ServerProvisioner
{
    /**
     * Create a new job instance.
     *
     * @param  \App\AppServer  $provisionable
     * @return void
     */
    public function __construct(AppServer $provisionable)
    {
        $this->provisionable = $provisionable;
    }
}
