<?php

namespace App\Jobs;

use App\Stack;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateLoadBalancerIfNecessary implements ShouldQueue
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
        $stack = $this->stack;

        if ($stack->balancers()->isEmpty()) {
            if ($stack->appServer || count($stack->webServers) === 1) {
                return;
            }

            $stack->environment->project->provisionBalancer(
                'balancer', $stack->recommendedBalancerSize()
            );
        }

        $stack->update(['balanced' => true]);
    }
}
