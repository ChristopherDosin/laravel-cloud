<?php

namespace App\Listeners;

use DateTime;
use App\User;
use App\Events\AlertCreated;

class UpdateLastAlertTimestampForCollaborators
{
    /**
     * Handle the event.
     *
     * @param  AlertCreated  $event
     * @return void
     */
    public function handle(AlertCreated $event)
    {
        User::whereIn('id', $event->affectedIds())->update(
            ['last_alert_received_at' => new DateTime]
        );
    }
}
