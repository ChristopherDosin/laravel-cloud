<?php

namespace App\Listeners;

use App\Contracts\Alertable;

class CreateAlert
{
    /**
     * Handle the event.
     *
     * @param  \App\Contracts\Alertable  $event
     * @return void
     */
    public function handle(Alertable $event)
    {
        $event->toAlert();
    }
}
