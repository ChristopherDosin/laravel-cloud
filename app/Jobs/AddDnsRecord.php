<?php

namespace App\Jobs;

use App\Stack;
use Illuminate\Bus\Queueable;
use App\Contracts\DnsProvider;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AddDnsRecord implements ShouldQueue
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
     * @param  \App\Contracts\DnsProvider  $dns
     * @return void
     */
    public function handle(DnsProvider $dns)
    {
        $dns->addRecord($this->stack);
    }
}
