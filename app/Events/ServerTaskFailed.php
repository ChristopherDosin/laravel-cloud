<?php

namespace App\Events;

use App\ServerTask;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ServerTaskFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The server task instance.
     *
     * @var \App\ServerTask
     */
    public $task;

    /**
     * Create a new event instance.
     *
     * @param  \App\ServerTask  $task
     * @return void
     */
    public function __construct(ServerTask $task)
    {
        $this->task = $task;
    }
}
