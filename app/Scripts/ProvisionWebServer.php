<?php

namespace App\Scripts;

use App\WebServer;

class ProvisionWebServer extends ProvisioningScript
{
    use WritesCaddyServerConfigurations;

    /**
     * The server instance.
     *
     * @var \App\WebServer
     */
    public $server;

    /**
     * Create a new script instance.
     *
     * @param  \App\WebServer  $server
     * @return void
     */
    public function __construct(WebServer $server)
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
        return "Provisioning Web Server ({$this->server->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.web.provision', [
            'script' => $this,
            'server' => $this->server,
            'customScripts' => $this->server->stack->meta['scripts']['web'] ?? [],
        ])->render();
    }
}
