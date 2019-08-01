<?php

namespace App\Scripts;

use App\AppServer;

class ProvisionAppServer extends ProvisioningScript
{
    use WritesCaddyServerConfigurations;

    /**
     * The server instance.
     *
     * @var \App\AppServer
     */
    public $server;

    /**
     * Create a new script instance.
     *
     * @param  \App\AppServer  $server
     * @return void
     */
    public function __construct(AppServer $server)
    {
        parent::__construct($server);

        $this->server = $server;
    }

    /**
     * Get the name of the script.
     *
     * @return string
     */
    public function name()
    {
        return "Provisioning App Server ({$this->server->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.app.provision', [
            'script' => $this,
            'server' => $this->server,
            'databasePassword' => $this->server->database_password,
            'customScripts' => $this->server->stack->meta['scripts']['app'] ?? [],
        ])->render();
    }
}
