<?php

namespace App\Http\Controllers\API;

use App\Balancer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBalancerRequest;
use Illuminate\Validation\ValidationException;

class BalancerController extends Controller
{
    /**
     * Get all of the balancers for the given project.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', $request->project);

        return $request->project->balancers()->with('address')->get();
    }

    /**
     * Handle the incoming request.
     *
     * @param  \App\Http\Requests\CreateBalancerRequest  $request
     * @return mixed
     */
    public function store(CreateBalancerRequest $request)
    {
        $this->authorize('view', $request->project);

        $balancer = $request->project->provisionBalancer(
            $request->name, $request->size, $request->tls === 'self-signed'
        );

        return response()->json($balancer, 201);
    }

    /**
     * Delete the given balancer.
     *
     * @param  \App\Balancer  $balancer
     * @return Response
     */
    public function destroy(Balancer $balancer)
    {
        $this->authorize('delete', $balancer);

        $balancer->load('project.environments.stacks.webServers');

        if (count($balancer->project->balancers) === 1 &&
            $balancer->project->allStacks()->contains->balanced) {
            throw ValidationException::withMessages(['balancer' => [
                'You may not delete the last load balancer if it is currently in use.'
            ]]);
        }

        $balancer->delete();
    }
}
