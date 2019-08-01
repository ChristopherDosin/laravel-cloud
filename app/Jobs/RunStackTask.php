<?php

namespace App\Jobs;

use App\StackTask;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RunStackTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The stack task.
     *
     * @var \App\StackTask
     */
    public $task;

    /**
     * Create a new job instance.
     *
     * @param  \App\StackTask  $task
     * @return void
     */
    public function __construct(StackTask $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->task->run();
    }
}
