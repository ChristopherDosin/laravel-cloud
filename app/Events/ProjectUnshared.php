<?php

namespace App\Events;

use App\User;
use App\Project;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ProjectUnshared
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The project instance.
     *
     * @var \App\Project
     */
    public $project;

    /**
     * The user instance.
     *
     * @var \App\User
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Project $project, User $user)
    {
        $this->user = $user;
        $this->project = $project;
    }
}
