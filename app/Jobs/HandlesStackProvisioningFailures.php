<?php

namespace App\Jobs;

use Exception;

trait HandlesStackProvisioningFailures
{
    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        try {
            $this->stack->delete();
        } catch (Exception $e) {
            report($e);
        }

        $this->stack->environment->project->alerts()->create([
            'type' => 'StackProvisioningFailed',
            'exception' => (string) $exception,
            'meta' => [],
        ]);
    }
}
