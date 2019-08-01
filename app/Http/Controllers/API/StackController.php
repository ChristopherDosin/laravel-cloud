<?php

namespace App\Http\Controllers\API;

use App\Stack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProvisionStackRequest;
use Illuminate\Validation\ValidationException;

class StackController extends Controller
{
    /**
     * Get all of the stacks for the project.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->authorize('view', $request->project);

        return $request->project->environments()->with('stacks')
                    ->when($request->environment, function ($query) use ($request) {
                        $query->where('id', $request->environment);
                    })->get()->flatMap->stacks->sortBy('name')->values();
    }

    /**
     * Provision a stack.
     *
     * @param  ProvisionStackRequest  $request
     * @return mixed
     */
    public function store(ProvisionStackRequest $request)
    {
        $this->authorize('view', $request->project());

        return response()->json(DB::transaction(function () use ($request) {
            return Stack::createForEnvironment($request->environment(), $request);
        })->provision(), 201);
    }

    /**
     * Delete the given stack.
     *
     * @param  \App\Stack  $stack
     * @return Response
     */
    public function destroy(Stack $stack)
    {
        $this->authorize('delete', $stack);

        if ($stack->isDeploying()) {
            throw ValidationException::withMessages([
                'stack' => ['A stack may not be deleted while it is deploying.'],
            ]);
        }

        $stack->delete();
    }
}
