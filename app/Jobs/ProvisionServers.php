<?php

namespace App\Jobs;

use App\Stack;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProvisionServers implements ShouldQueue
{
    use Dispatchable, HandlesStackProvisioningFailures, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * Create a new job instance.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function __construct(Stack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->stack->update([
            'initial_server_count' => count($this->stack->allServers()),
        ]);

        foreach ($this->stack->allServers() as $server) {
            if (! $server->providerServerId()) {
                $server->update([
                    'provider_server_id' => $this->createServer($server),
                ]);
            }

            if (! $server->provisioningJobDispatched()) {
                $server->provision();
            }
        }
    }

    /**
     * Create a server on the server provider.
     *
     * @param  \App\Contracts\Provisionable  $server
     * @return string
     */
    protected function createServer($server)
    {
        $region = $this->stack->region();

        return $this->stack->environment->project
                        ->withProvider()
                        ->createServer($server->name, $server->size, $region);
    }
}
