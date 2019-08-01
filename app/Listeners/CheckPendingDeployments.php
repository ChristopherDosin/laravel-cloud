<?php

namespace App\Listeners;

use App\Events\DeploymentFinished;

class CheckPendingDeployments
{
    /**
     * Handle the event.
     *
     * @param  DeploymentFinished  $event
     * @return void
     */
    public function handle(DeploymentFinished $event)
    {
        $event->deployment->stack->deployPending();
    }
}
