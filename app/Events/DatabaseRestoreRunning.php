<?php

namespace App\Events;

use App\DatabaseRestore;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DatabaseRestoreRunning
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The database restore instance.
     *
     * @var \App\DatabaseRestore
     */
    public $restore;

    /**
     * Create a new event instance.
     *
     * @param  \App\DatabaseRestore  $restore
     * @return void
     */
    public function __construct(DatabaseRestore $restore)
    {
        $this->restore = $restore;
    }
}
