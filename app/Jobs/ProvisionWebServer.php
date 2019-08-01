<?php

namespace App\Jobs;

use App\WebServer;

class ProvisionWebServer extends ServerProvisioner
{
    /**
     * Create a new job instance.
     *
     * @param  \App\WebServer  $provisionable
     * @return void
     */
    public function __construct(WebServer $provisionable)
    {
        $this->provisionable = $provisionable;
    }
}
