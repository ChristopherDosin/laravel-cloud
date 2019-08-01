<?php

namespace App\Http\Controllers\API;

use App\Stack;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class SchedulerController extends Controller
{
    /**
     * Install the scheduler for the stack.
     *
     * @param  Request  $request
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function store(Request $request, Stack $stack)
    {
        $this->authorize('view', $stack);

        if (! $deployment = $stack->lastDeployment()) {
            throw ValidationException::withMessages([
                'stack' => ['This stack does not have any deployments.'],
            ]);
        }

        $deployment->serverDeployments->each->startScheduler();

        return response('', 201);
    }

    /**
     * Remove the scheduler for the stack.
     *
     * @param  Request  $request
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function destroy(Request $request, Stack $stack)
    {
        $this->authorize('view', $stack);

        if (! $deployment = $stack->lastDeployment()) {
            throw ValidationException::withMessages([
                'stack' => ['This stack does not have any deployments.'],
            ]);
        }

        $deployment->serverDeployments->each->stopScheduler();
    }
}
