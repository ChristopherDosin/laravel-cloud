<?php

namespace App\Scripts;

use App\DatabaseBackup;

class StoreDatabaseBackup extends Script
{
    /**
     * The user that the script should be run as.
     *
     * @var string
     */
    public $sshAs = 'cloud';

    /**
     * The database backup instance.
     *
     * @var \App\DatabaseBackup
     */
    public $backup;

    /**
     * Create a new script instance.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return void
     */
    public function __construct(DatabaseBackup $backup)
    {
        $this->backup = $backup;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Storing Database Backup ({$this->backup->database->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.database.backup', [
            'script' => $this,
            'backup' => $this->backup,
        ])->render();
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 3600;
    }
}
