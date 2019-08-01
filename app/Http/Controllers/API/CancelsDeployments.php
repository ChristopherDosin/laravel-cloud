<?php

namespace App\Http\Controllers\API;

use App\Deployment;

trait CancelsDeployments
{
    /**
     * Attempt to cancel the given deployment.
     *
     * @param  \App\Deployment  $deployment
     * @return Response
     */
    protected function cancel(Deployment $deployment)
    {
        $deployment->stack->resetDeploymentStatus();

        if (! $deployment->cancel()) {
            return response()->json([
                'deployment' => ['We were unable to cancel this deployment.'],
            ], 400);
        }
    }
}
