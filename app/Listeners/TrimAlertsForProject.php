<?php

namespace App\Listeners;

use App\Events\AlertCreated;

class TrimAlertsForProject
{
    /**
     * Handle the event.
     *
     * @param  AlertCreated  $event
     * @return void
     */
    public function handle(AlertCreated $event)
    {
        $alerts = $event->alert->project->alerts()->get();

        if (count($alerts) > 30) {
            $alerts->slice(30 - count($alerts))->each->delete();
        }
    }
}
