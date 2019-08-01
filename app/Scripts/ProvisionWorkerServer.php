<?php

namespace App\Scripts;

use App\WorkerServer;

class ProvisionWorkerServer extends ProvisioningScript
{
    /**
     * The server instance.
     *
     * @var \App\WorkerServer
     */
    public $server;

    /**
     * Create a new script instance.
     *
     * @param  \App\WorkerServer  $server
     * @return void
     */
    public function __construct(WorkerServer $server)
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
        return "Provisioning Worker Server ({$this->server->name})";
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.worker.provision', [
            'script' => $this,
            'customScripts' => $this->server->stack->meta['scripts']['worker'] ?? [],
        ])->render();
    }
}
