<?php

namespace App\Jobs;

use App\ServerDeployment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Scripts\StartScheduler as StartSchedulerScript;

class StartScheduler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The server deployment instance.
     *
     * @var \App\ServerDeployment
     */
    public $deployment;

    /**
     * Create a new job instance.
     *
     * @param  \App\ServerDeployment  $deployment
     * @return void
     */
    public function __construct(ServerDeployment $deployment)
    {
        $this->deployment = $deployment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->deployment->schedule())) {
            return $this->delete();
        }

        if ($this->deployment->deployable->isWorker()) {
            $this->deployment->deployable->runInBackground(
                new StartSchedulerScript($this->deployment)
            );
        }
    }
}
