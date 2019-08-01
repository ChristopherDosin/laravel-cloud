<?php

namespace App\Http\Controllers\API;

use App\Hook;
use App\Http\Controllers\Controller;
use App\Exceptions\AlreadyDeployingException;
use App\Http\Requests\CreateHookDeploymentRequest;

class HookDeploymentController extends Controller
{
    /**
     * Create a new deployment for the hook.
     *
     * @param  \App\Http\Requests\CreateHookDeploymentRequest  $request
     * @param  \App\Hook  $hook
     * @param  string  $token
     * @return Response
     */
    public function store(CreateHookDeploymentRequest $request, Hook $hook, $token)
    {
        abort_if($hook->token !== $token, 403);

        if ($hook->isTest($request->all())) {
            return;
        }

        if (! $request->receivable() || ! $hook->receives($request->all())) {
            return response('', 204);
        }

        try {
            $instructions = $request->instructions();

            return response()->json($hook->stack->deployHash(
                $instructions->hash,
                $instructions->build, $instructions->activate,
                $instructions->directories, $instructions->daemons
            ), 201);
        } catch (AlreadyDeployingException $e) {
            $hook->stack->storePendingDeployment($hook, $request->hash());

            return response('', 202);
        }
    }
}
