<?php

namespace App\Http\Controllers\API;

use App\Stack;
use App\Deployment;
use Illuminate\Http\Request;
use App\DeploymentInstructions;
use App\Http\Controllers\Controller;
use App\Exceptions\AlreadyDeployingException;
use App\Http\Requests\CreateDeploymentRequest;
use Illuminate\Validation\ValidationException;

class DeploymentController extends Controller
{
    use CancelsDeployments;

    /**
     * Get the recent deployments for the given stack.
     *
     * @param  Request  $request
     * @param  \App\Stack  $stack
     * @return Response
     */
    public function index(Request $request, Stack $stack)
    {
        $this->authorize('view', $stack);

        return $stack->deployments()->take(10)->get();
    }

    /**
     * Get the deployment with the given ID.
     *
     * @param \App\Deployment  $deployment
     * @return Response
     */
    public function show(Deployment $deployment)
    {
        $this->authorize('view', $deployment->stack);

        return $deployment->load([
            'serverDeployments.deployable',
            'serverDeployments.buildTask',
            'serverDeployments.activationTask'
        ]);
    }

    /**
     * Create a new deployment for the stack.
     *
     * @param  \App\Http\Requests\CreateDeploymentRequest  $request
     * @return Response
     */
    public function store(CreateDeploymentRequest $request)
    {
        $this->authorize('view', $request->stack);

        if (! $request->stack->isProvisioned()) {
            throw ValidationException::withMessages([
                'stack' => ['This stack has not finished provisioning.'],
            ]);
        }

        $instructions = DeploymentInstructions::fromRequest($request);

        try {
            $deployment = $request->stack->deployUsing($instructions);

            $deployment->update([
                'initiator_id' => $request->user()->id ?? null,
            ]);

            return response($deployment, 201);
        } catch (AlreadyDeployingException $e) {
            return response('', 409);
        }
    }

    /**
     * Cancel the given deployment.
     *
     * @param  \App\Deployment  $deployment
     * @return Response
     */
    public function destroy(Deployment $deployment)
    {
        $this->authorize('view', $deployment->stack);

        return $this->cancel($deployment);
    }
}
