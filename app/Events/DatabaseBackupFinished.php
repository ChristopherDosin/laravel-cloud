<?php

namespace App\Events;

use App\DatabaseBackup;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class DatabaseBackupFinished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The database backup instance.
     *
     * @var \App\DatabaseBackup
     */
    public $backup;

    /**
     * Create a new event instance.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return void
     */
    public function __construct(DatabaseBackup $backup)
    {
        $this->backup = $backup;
    }
}
