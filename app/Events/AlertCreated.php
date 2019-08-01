<?php

namespace App\Events;

use App\Alert;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class AlertCreated
{
    use Dispatchable, SerializesModels;

    /**
     * The alert instance.
     *
     * @var \App\Alert
     */
    public $alert;

    /**
     * Create a new event instance.
     *
     * @param  \App\Alert  $alert
     * @return void
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Get the user IDs affected by this alert.
     *
     * @return array
     */
    public function affectedIds()
    {
        return collect([$this->alert->project->user])->merge(
            $this->alert->project->collaborators
        )->pluck('id')->all();
    }
}
