<?php

namespace App\Http\Controllers\API;

use App\Environment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EnvironmentHookController extends Controller
{
    /**
     * Get the hooks for the given environment.
     *
     * @param  Request  $request
     * @param  \App\Environment  $environment
     * @return Response
     */
    public function index(Request $request, Environment $environment)
    {
        $this->authorize('view', $environment->project);

        return $environment->stacks->load('hooks.stack')
                            ->flatMap
                            ->hooks
                            ->sortBy('name')
                            ->sortBy('stack.name')
                            ->values();
    }
}
