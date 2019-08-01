<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Jobs\ProvisionWebServer;

class WebServer extends HttpServer
{
    /**
     * Determine if this server will run a given deployment command.
     *
     * @param  string  $command
     * @return bool
     */
    public function runsCommand($command)
    {
        return ! Str::startsWith($command, 'worker:');
    }

    /**
     * Determine if this server is the "master" server for the stack.
     *
     * @return bool
     */
    public function isMaster()
    {
        return $this->is($this->stack->masterServer());
    }

    /**
     * Dispatch the job to provision the server.
     *
     * @return void
     */
    public function provision()
    {
        ProvisionWebServer::dispatch($this);

        $this->update(['provisioning_job_dispatched_at' => Carbon::now()]);
    }

    /**
     * Get the provisioning script for the server.
     *
     * @return \App\Scripts\Script
     */
    public function provisioningScript()
    {
        return new Scripts\ProvisionWebServer($this);
    }
}
