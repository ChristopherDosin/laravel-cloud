<?php

namespace App\Jobs;

use App\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class FinishTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The task instance.
     *
     * @var \App\Task
     */
    public $task;

    /**
     * The task script's exit code.
     *
     * @var int
     */
    public $exitCode;

    /**
     * Create a new job instance.
     *
     * @param  \App\Task  $task
     * @param  int  $exitCode
     * @return void
     */
    public function __construct(Task $task, $exitCode = 0)
    {
        $this->task = $task;
        $this->exitCode = $exitCode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->task->finish($this->exitCode);
    }
}
