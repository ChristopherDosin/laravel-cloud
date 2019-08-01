<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SshBalancerController extends Controller
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

        return $request->project->balancers()
                        ->with('address')
                        ->get()
                        ->filter
                        ->canSsh($request->user());
    }
}
