<?php

namespace App\Jobs;

use App\Stack;
use Illuminate\Bus\Queueable;
use App\Mail\StackProvisioned;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MarkStackAsProvisioned implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $this->stack->markAsProvisioned();

        Mail::to($this->stack->environment->project->user)->send(
            new StackProvisioned($this->stack)
        );
    }
}
