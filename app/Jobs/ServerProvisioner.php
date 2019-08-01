<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use App\Contracts\Provisionable;
use Illuminate\Queue\SerializesModels;
use App\Exceptions\ProvisioningTimeout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ServerProvisioner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The provisionable instance.
     *
     * @var Provisionable
     */
    public $provisionable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 40; // 20 Total Minutes...

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->provisionable->isProvisioned()) {
            try {
                $this->provisioned();
            } catch (Exception $e) {
                report($e);
            }

            return $this->delete();
        } elseif ($this->provisionable->olderThan(15)) {
            return $this->fail(ProvisioningTimeout::for($this->provisionable));
        } elseif ($this->provisionable->isProvisioning()) {
            return $this->release(30);
        } elseif ($this->provisionable->isReadyForProvisioning()) {
            $this->provisionable->runProvisioningScript();
        }

        $this->release(30);
    }

    /**
     * Perform any tasks after the server is provisioned.
     *
     * @return void
     */
    protected function provisioned()
    {
        //
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        try {
            $this->provisionable->delete();
        } catch (Exception $e) {
            report($e);
        }

        $this->provisionable->project->alerts()->create([
            'type' => 'ServerProvisioningFailed',
            'exception' => (string) $exception,
            'meta' => [],
        ]);
    }
}
