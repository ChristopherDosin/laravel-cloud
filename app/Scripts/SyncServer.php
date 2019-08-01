<?php

namespace App\Scripts;

use App\Server;

class SyncServer extends Script
{
    use WritesCaddyServerConfigurations;

    /**
     * The server instance.
     *
     * @var \App\Server
     */
    public $server;

    /**
     * Create a new script instance.
     *
     * @param  \App\Server  $server
     * @return void
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Syncing Server Configuration ({$this->server->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.server.sync', ['script' => $this])->render();
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
