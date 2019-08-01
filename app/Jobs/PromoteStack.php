<?php

namespace App\Jobs;

use App\Stack;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PromoteStack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The stack instance.
     *
     * @var \App\Stack
     */
    public $stack;

    /**
     * The stack promotion options.
     *
     * @var array
     */
    public $options = [];

    /**
     * Create a new job instance.
     *
     * @param  \App\Stack  $stack
     * @param  array  $options
     * @return void
     */
    public function __construct(Stack $stack, array $options = [])
    {
        $this->stack = $stack;

        $this->options = array_merge([
            'hooks' => true,
            'wait' => false,
        ], $options);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        [$stack, $previous] = [
            $this->stack, $this->stack->environment->promotedStack(),
        ];

        $stack->environment->markAsPromoted($stack);

        // Once the new stack has been promoted, we need to sync the balancers so that
        // the production URLs will be served by the new stack. We will dispatch it
        // synchronously so that it totally finishes within this task's lifetime.
        Bus::dispatchNow(
            new SyncBalancers($stack->project())
        );

        // If this stack is in the production environment, we'll enable its background
        // services such as its queue workers and schedulers. This will allow it to
        // begin fully functioning as a new production stack for the application.
        if ($stack->environment->isProduction() &&
            ! $this->options['wait']) {
            $stack->allServers()->each->startBackgroundServices();
        }

        // If there was a previously promoted stack we will disable all the background
        // services on that stack. This will prevent it from processing queued jobs
        // or running schedulers, letting this newly promoted stack to take over.
        if ($stack->environment->isProduction() && $previous) {
            $previous->allServers()->each->stopBackgroundServices();
        }

        // If there was a previously promoted stack and the deployment hooks should be
        // transferred to the newly promoted stack, we will update the stack ID for
        // the previously promoted stack's hooks so they point to this new stack.
        if ($previous && $this->options['hooks']) {
            $previous->hooks()->update([
                'stack_id' => $stack->id,
            ]);
        }

        $stack->environment->promotionLock()->release();
    }
}
