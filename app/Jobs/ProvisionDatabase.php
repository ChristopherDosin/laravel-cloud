<?php

namespace App\Jobs;

use App\Database;
use App\Mail\DatabaseProvisioned;
use Illuminate\Support\Facades\Mail;

class ProvisionDatabase extends ServerProvisioner
{
    /**
     * Create a new job instance.
     *
     * @param  \App\Database  $provisionable
     * @return void
     */
    public function __construct(Database $provisionable)
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
            new DatabaseProvisioned($this->provisionable)
        );
    }
}
