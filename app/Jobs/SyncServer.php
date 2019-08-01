<?php

namespace App\Jobs;

use App\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Scripts\SyncServer as SyncServerScript;

class SyncServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The server instance.
     *
     * @var \App\Server
     */
    public $server;

    /**
     * Create a new job instance.
     *
     * @param  \App\Server  $server
     * @return void
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->server->run(new SyncServerScript($this->server));
    }
}
