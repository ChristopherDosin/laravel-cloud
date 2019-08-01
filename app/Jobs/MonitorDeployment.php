<?php

namespace App\Jobs;

use Exception;
use App\Deployment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class MonitorDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The deployment instance.
     *
     * @var \App\Deployment
     */
    public $deployment;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 300; // 25 Total Minutes...

    /**
     * Create a new job instance.
     *
     * @param  \App\Deployment  $deployment
     * @return void
     */
    public function __construct(Deployment $deployment)
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
        // If the deployment has been activated, then we can mark this as finished and
        // delete the job. If it has failures, we will need to mark this deployment
        // as failed and delete the job so the job monitoring will cease running.
        if ($this->deployment->isActivated()) {
            $this->deployment->markAsFinished();

            return $this->delete();
        }

        if ($this->deployment->wasCancelled() ||
            $this->deployment->isTimedOut()) {
            return $this->delete();
        }

        // if the deployment has failures, we will mark this as failed and delete the
        // job so it no longer monitors this deployment. We'll also delete it when
        // this deployment has been running for too long and has just timed out.
        if ($this->deployment->hasFailures()) {
            $this->deployment->markAsFailed();

            return $this->delete();
        }

        if ($this->deployment->olderThan(20)) {
            $this->deployment->markAsTimedOut();

            return $this->delete();
        }

        // If this deploymnet has completed building we will fire off the activation
        // job and release this job to keep monitoring this deployment's progress
        // as activation continues. Then the deloyment will be really finished.
        if ($this->deployment->isBuilt()) {
            $this->deployment->activate();
        }

        $this->release(5);
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        $this->deployment->markAsFailed($exception);
    }
}
