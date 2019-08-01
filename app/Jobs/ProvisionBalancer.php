<?php

namespace App\Jobs;

use App\Balancer;
use App\Mail\BalancerProvisioned;
use Illuminate\Support\Facades\Mail;

class ProvisionBalancer extends ServerProvisioner
{
    /**
     * Create a new job instance.
     *
     * @param  \App\Balancer  $provisionable
     * @return void
     */
    public function __construct(Balancer $provisionable)
    {
        $this->provisionable = $provisionable;
    }

    /**
     * Perform any tasks after the server is provisioned.
     *
     * @return void
     */
    protected function provisioned()
    {
        Mail::to($this->provisionable->project->user)->queue(
            new BalancerProvisioned($this->provisionable)
        );
    }
}
