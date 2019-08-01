<?php

namespace App\Http\Controllers\API;

use App\Stack;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;

class DaemonController extends Controller
{
    /**
     * Update the daemon states for the stack.
     *
     * @param  Request  $request
     * @param  \App\Stack  $stack
     * @return mixed
     */
    public function update(Request $request, Stack $stack)
    {
        $this->authorize('view', $stack->project());

        $request->validate([
            'action' => 'required|string|in:start,restart,pause,continue,unpause'
        ]);

        if (! $deployment = $stack->lastDeployment()) {
            throw ValidationException::withMessages([
                'stack' => ['This stack does not have any deployments.'],
            ]);
        }

        switch ($request->action) {
            case 'start':
            case 'restart':
                $deployment->serverDeployments->each->restartDaemons();
                break;

            case 'pause':
                $deployment->serverDeployments->each->pauseDaemons();
                break;

            case 'continue':
            case 'unpause':
                $deployment->serverDeployments->each->unpauseDaemons();
                break;
        }
    }
}
