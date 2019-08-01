<?php

namespace App;

use Carbon\Carbon;
use App\Jobs\ProvisionAppServer;

class AppServer extends HttpServer
{
    /**
     * Determine if this server will run a given deployment command.
     *
     * @param  string  $command
     * @return bool
     */
    public function runsCommand($command)
    {
        return true;
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
     * Determine if this server processes queued jobs.
     *
     * @return bool
     */
    public function isWorker()
    {
        return true;
    }

    /**
     * Determine if this server is the "master" worker for the stack.
     *
     * @return bool
     */
    public function isMasterWorker()
    {
        return $this->is($this->stack->masterWorker());
    }

    /**
     * Dispatch the job to provision the server.
     *
     * @return void
     */
    public function provision()
    {
        ProvisionAppServer::dispatch($this);

        $this->update(['provisioning_job_dispatched_at' => Carbon::now()]);
    }

    /**
     * Get the provisioning script for the server.
     *
     * @return \App\Scripts\Script
     */
    public function provisioningScript()
    {
        return new Scripts\ProvisionAppServer($this);
    }
}
