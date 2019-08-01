<?php

namespace App\Listeners;

use App\Contracts\HasStack;

class ResetDeploymentStatus
{
    /**
     * Handle the event.
     *
     * @param  \App\Contracts\HasStack  $event
     * @return void
     */
    public function handle(HasStack $event)
    {
        $event->stack()->resetDeploymentStatus();
    }
}
