<?php

namespace App\Scripts;

use App\Database;

class SyncNetwork extends Script
{
    /**
     * The database instance.
     *
     * @var \App\Database
     */
    public $database;

    /**
     * Create a new script instance.
     *
     * @param  \App\Database  $database
     * @return void
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Networking Database Servers ({$this->database->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.database.network', [
            'script' => $this,
            'database' => $this->database,
            'ipAddresses' => $this->database->shouldAllowAccessFrom(),
            'previousIpAddresses' => $this->database->allows_access_from,
        ])->render();
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 15;
    }
}
